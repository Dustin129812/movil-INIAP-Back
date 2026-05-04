<?php

namespace Modules\Investigacion\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Notifications\PlannerAccept;
use ZipArchive;

class PlanningReviewService
{
    public function getWeeklyPlanningData(User $revisor, string $period = '15days'): Collection
    {
        $isDirector = $revisor->hasRole('station-director');
        $managedGroups = $this->getManagedGroups($revisor);

        if (!$isDirector && $managedGroups->isEmpty()) {
            return collect();
        }

        $dateFilter = $this->getDateFilter($period);
        $statuses = ['pending', 'approved', 'rejected', 'reassigned', 'completed'];

        $ownActivities = $this->fetchOwnActivities($managedGroups, $statuses, $dateFilter, $revisor, $isDirector);

        $ownActivities->each(function($act) use ($managedGroups) {
            $userName = $act->user ? $act->user->name : 'Usuario Desconocido';
            $group = $this->findMatchingGroup($act->user_id, $managedGroups);
            $this->injectMetadata($act, true, $act->user_id, $userName, $userName, $group);
        });

        $supportActivities = $this->fetchSupportActivities($managedGroups, $statuses, $dateFilter, $revisor, $isDirector);
        $decoratedSupport = collect();

        $directResponsibles = $isDirector
            ? $managedGroups->whereNull('parent_id')->pluck('responsible_id')->unique()->filter()->toArray()
            : [];

        foreach ($supportActivities as $act) {
            foreach ($act->logisticSupportUsers as $sUser) {
                $group = $this->findMatchingGroup($sUser->id, $managedGroups);
                $shouldInclude = false;

                if ($isDirector) {
                    $isHistorical = in_array($act->status, ['completed', 'approved']);
                    $isDirectResponsible = in_array($sUser->id, $directResponsibles);

                    if ($isHistorical || $isDirectResponsible) {
                        $shouldInclude = true;
                    }
                } else {
                    if ($group) {
                        $shouldInclude = true;
                    }
                }

                if ($shouldInclude) {
                    $cloned = clone $act;
                    $ownerName = $act->user ? $act->user->name : 'Usuario Desconocido';
                    $this->injectMetadata($cloned, false, $sUser->id, $sUser->name, $ownerName, $group);
                    $decoratedSupport->push($cloned);
                }
            }
        }

        return $ownActivities->concat($decoratedSupport);
    }

    private function getManagedGroups(User $revisor): Collection
    {
        $query = Group::with('members');

        if ($revisor->hasRole('station-director')) {
            return $query->where('location_id', $revisor->location_id)->get();
        }

        $directGroups = $query->where('location_id', $revisor->location_id)
            ->where('responsible_id', $revisor->id)
            ->get();

        $allManagedGroups = collect($directGroups);
        $parentIds = $directGroups->pluck('id')->toArray();

        while (!empty($parentIds)) {
            $childGroups = Group::with('members')
                ->whereIn('parent_id', $parentIds)
                ->whereNotIn('id', $allManagedGroups->pluck('id')->toArray())
                ->get();

            if ($childGroups->isEmpty()) {
                break;
            }

            $allManagedGroups = $allManagedGroups->concat($childGroups);
            $parentIds = $childGroups->pluck('id')->toArray();
        }

        return $allManagedGroups;
    }

    private function fetchOwnActivities(Collection $managedGroups, array $statuses, ?Carbon $date, User $revisor, bool $isDirector): Collection
    {
        return WeekActivity::where(function ($q) use ($managedGroups, $revisor, $isDirector, $statuses) {
            if ($isDirector) {
                $directResponsibles = $managedGroups->whereNull('parent_id')
                    ->pluck('responsible_id')
                    ->unique()
                    ->filter();

                $allStationMembers = $managedGroups->flatMap->members->pluck('id')->unique();

                $q->where(function ($subQ) use ($directResponsibles) {
                    $subQ->whereIn('status', ['pending', 'rejected', 'reassigned'])
                        ->whereIn('user_id', $directResponsibles);
                })->orWhere(function ($subQ) use ($allStationMembers) {
                    $subQ->whereIn('status', ['completed', 'approved'])
                        ->whereIn('user_id', $allStationMembers);
                });
            } else {
                $memberIds = $managedGroups->flatMap->members->pluck('id')->unique()
                    ->reject(fn($id) => $id == $revisor->id);

                $q->whereIn('status', $statuses)
                    ->whereIn('user_id', $memberIds);
            }
        })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators'])
            ->when($date, function($q) use ($date) {
                $q->where('date', '>=', $date);
            })
            ->orderBy('date', 'desc')
            ->get();
    }

    private function fetchSupportActivities(Collection $managedGroups, array $statuses, ?Carbon $date, User $revisor, bool $isDirector): Collection
    {
        return WeekActivity::whereHas('logisticSupportUsers', function ($sq) {
            $sq->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
        })
            ->where(function($q) use ($managedGroups, $revisor, $isDirector, $statuses) {
                if ($isDirector) {
                    $directResponsibles = $managedGroups->whereNull('parent_id')->pluck('responsible_id')->unique()->filter();
                    $allStationMembers = $managedGroups->flatMap->members->pluck('id')->unique();

                    $q->where(function ($subQ) use ($directResponsibles) {
                        $subQ->whereIn('status', ['pending', 'rejected', 'reassigned'])
                            ->whereHas('logisticSupportUsers', fn($sq) => $sq->whereIn('users.id', $directResponsibles));
                    })->orWhere(function ($subQ) use ($allStationMembers) {
                        $subQ->whereIn('status', ['completed', 'approved'])
                            ->whereHas('logisticSupportUsers', fn($sq) => $sq->whereIn('users.id', $allStationMembers));
                    });
                } else {
                    $memberIds = $managedGroups->flatMap->members->pluck('id')->unique();
                    $q->whereIn('status', $statuses)
                        ->whereHas('logisticSupportUsers', fn($sq) => $sq->whereIn('users.id', $memberIds));
                }
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators', 'logisticSupportUsers'])
            ->when($date, function($q) use ($date) {
                $q->where(function($query) use ($date) {
                    $query->where('date', '>=', $date)
                        ->orWhereIn('status', ['pending', 'reassigned']);
                });
            })
            ->orderBy('date', 'desc')
            ->get();
    }

    private function findMatchingGroup(int $userId, Collection $managedGroups)
    {
        return $managedGroups->first(function ($g) use ($userId) {
            return $g->members->contains('id', $userId);
        });
    }

    public function updateActivityStatus(int $activityId, string $status, User $approver): array
    {
        return DB::transaction(function () use ($activityId, $status, $approver) {
            $weekActivity = WeekActivity::findOrFail($activityId);
            $weekActivity->status = $status;

            if (!$weekActivity->save()) {
                throw new \Exception("No se pudo guardar la actividad en base de datos.");
            }

            $creator = $weekActivity->user;

            if ($creator && $approver && $creator->id !== $approver->id) {
                $creator->notify(new PlannerAccept($weekActivity, $approver, $status));
            }

            return [
                'activity_id' => $activityId,
                'status' => $status,
            ];
        });
    }

    private function injectMetadata($act, bool $isOwner, $displayId, $displayName, $ownerName, $group = null): void
    {
        $act->setAttribute('is_owner', $isOwner);
        $act->setAttribute('display_user_id', $displayId);
        $act->setAttribute('display_user_name', $displayName);
        $act->setAttribute('ownerName', $ownerName);
        $act->setAttribute('assigned_group', $group);
    }

    private function getDateFilter(string $period): ?Carbon
    {
        return match($period) {
            '7days' => Carbon::now()->subDays(7),
            '15days' => Carbon::now()->subDays(15),
            'all' => null,
            default => null
        };
    }

    public function generateUserEvidenceZip(int $userId, string $period = '15days'): string
    {
        $revisor = auth()->user();
        $dateFilter = $this->getDateFilter($period);

        // Obtenemos todas las actividades de ese usuario que tengan evidencias
        $activities = WeekActivity::where('user_id', $userId)
            ->whereNotNull('evidence_path')
            ->when($dateFilter, fn($q) => $q->where('date', '>=', $dateFilter))
            ->get();

        if ($activities->isEmpty()) {
            throw new \Exception("El usuario no tiene archivos verificables en este periodo.");
        }

        $zipFileName = 'evidencias_user_' . $userId . '_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($activities as $act) {
                foreach ($act->evidence_path as $path) {
                    $fullPath = Storage::disk('verificables_externos')->path($path);
                    if (file_exists($fullPath)) {
                        $zip->addFile($fullPath, $act->date . '/' . basename($path));
                    }
                }
            }
            $zip->close();
        }

        return $zipPath;
    }
}

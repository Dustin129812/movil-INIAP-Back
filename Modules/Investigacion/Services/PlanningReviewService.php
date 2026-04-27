<?php

namespace Modules\Investigacion\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Notifications\PlannerAccept;

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

        foreach ($supportActivities as $act) {
            foreach ($act->logisticSupportUsers as $sUser) {
                $group = $this->findMatchingGroup($sUser->id, $managedGroups);

                if ($group || $isDirector) {
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

        return $query->where('location_id', $revisor->location_id)
            ->where('responsible_id', $revisor->id)
            ->get();
    }

    private function fetchOwnActivities(Collection $managedGroups, array $statuses, ?Carbon $date, User $revisor, bool $isDirector): Collection
    {
        $memberIds = $managedGroups->flatMap->members->pluck('id')->unique();

        return WeekActivity::whereIn('status', $statuses)
            ->where(function ($q) use ($memberIds, $revisor, $isDirector) {
                if ($isDirector) {
                    $q->whereHas('user', fn($u) => $u->where('location_id', $revisor->location_id));
                } else {
                    $q->whereIn('user_id', $memberIds);
                }
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators'])
            ->when($date, function($q) use ($date) {
                $q->where(function($query) use ($date) {
                    $query->where('date', '>=', $date)
                        ->orWhere('status', 'pending');
                });
            })
            ->orderBy('date', 'desc')
            ->get();
    }

    private function fetchSupportActivities(Collection $managedGroups, array $statuses, ?Carbon $date, User $revisor, bool $isDirector): Collection
    {
        $memberIds = $managedGroups->flatMap->members->pluck('id')->unique();

        return WeekActivity::whereIn('status', $statuses)
            ->where(function($q) use ($memberIds, $revisor, $isDirector) {
                $q->whereHas('logisticSupportUsers', function ($sq) use ($memberIds, $revisor, $isDirector) {
                    $sq->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                    if ($isDirector) {
                        $sq->where('location_id', $revisor->location_id);
                    } else {
                        $sq->whereIn('users.id', $memberIds);
                    }
                });
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators', 'logisticSupportUsers'])
            ->when($date, function($q) use ($date) {
                $q->where(function($query) use ($date) {
                    $query->where('date', '>=', $date)
                        ->orWhere('status', 'pending');
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

    /**
     * Extraemos la lógica de estado del controlador al servicio (Fat Service)
     * e implementamos DB::transaction para garantizar la integridad en PostgreSQL.
     */
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

    /**
     * 🔧 FIX: Ahora retorna null cuando period='all' para mostrar TODO
     */
    private function getDateFilter(string $period): ?Carbon
    {
        return match($period) {
            '7days' => Carbon::now()->subDays(7),
            '15days' => Carbon::now()->subDays(15),
            'all' => null,
            default => null
        };
    }
}

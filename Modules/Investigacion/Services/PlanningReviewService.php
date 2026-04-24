<?php

namespace Modules\Investigacion\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Entities\WeekActivity;

class PlanningReviewService
{
    public function getWeeklyPlanningData(User $revisor, string $period = '15days'): Collection
    {
        // 1. Ahora obtenemos los modelos completos de los grupos, no solo los IDs
        $managedGroups = $this->getManagedGroups($revisor);

        if ($managedGroups->isEmpty()) {
            return collect();
        }

        $dateFilter = $this->getDateFilter($period);
        $statuses = ['pending', 'approved', 'rejected', 'reassigned'];

        // 2. Buscamos y decoramos las propias
        $ownActivities = $this->fetchOwnActivities($managedGroups, $statuses, $dateFilter);
        $ownActivities->each(function($act) use ($managedGroups) {
            $userName = $act->user ? $act->user->name : 'Usuario Desconocido';
            $group = $this->findMatchingGroup($act->activity->product, $managedGroups);
            $this->injectMetadata($act, true, $act->user_id, $userName, $userName, $group);
        });

        // 3. Buscamos y decoramos apoyos
        $supportActivities = $this->fetchSupportActivities($managedGroups, $statuses, $dateFilter);
        $decoratedSupport = collect();

        foreach ($supportActivities as $act) {
            foreach ($act->logisticSupportUsers as $sUser) {
                $cloned = clone $act;
                $ownerName = $act->user ? $act->user->name : 'Usuario Desconocido';
                $group = $this->findMatchingGroup($act->activity->product, $managedGroups);
                $this->injectMetadata($cloned, false, $sUser->id, $sUser->name, $ownerName, $group);
                $decoratedSupport->push($cloned);
            }
        }

        return $ownActivities->concat($decoratedSupport);
    }

    private function getManagedGroups(User $revisor): Collection
    {
        if ($revisor->location && $revisor->location->is_central) {
            return Group::where('location_id', $revisor->location_id)
                ->where('responsible_id', $revisor->id)
                ->get();
        }

        if ($revisor->hasRole('station-director')) {
            return Group::where('location_id', $revisor->location_id)->get();
        }

        return Group::where('location_id', $revisor->location_id)
            ->where('responsible_id', $revisor->id)
            ->get();
    }

    /**
     * Busca las actividades cuyos productos coincidan con el rubro y localidad de los grupos.
     */
    private function fetchOwnActivities(Collection $managedGroups, array $statuses, ?Carbon $date): Collection
    {
        return WeekActivity::whereIn('status', $statuses)
            ->whereHas('activity.product', function ($q) use ($managedGroups) {
                // Filtramos por las combinaciones (rubro + location) de los grupos
                $q->where(function ($query) use ($managedGroups) {
                    foreach ($managedGroups as $group) {
                        $query->orWhere(function ($subQ) use ($group) {
                            $subQ->where('rubro_id', $group->rubro_id)
                                ->where('location_id', $group->location_id);
                        });
                    }
                });
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators'])
            ->when($date, fn($q) => $q->where('date', '>=', $date))
            ->orderBy('date', 'desc')
            ->get();
    }

    private function fetchSupportActivities(Collection $managedGroups, array $statuses, ?Carbon $date): Collection
    {
        return WeekActivity::whereIn('status', $statuses)
            ->whereHas('activity.product', function ($q) use ($managedGroups) {
                $q->where(function ($query) use ($managedGroups) {
                    foreach ($managedGroups as $group) {
                        $query->orWhere(function ($subQ) use ($group) {
                            $subQ->where('rubro_id', $group->rubro_id)
                                ->where('location_id', $group->location_id);
                        });
                    }
                });
            })
            ->whereHas('logisticSupportUsers', function ($q) {
                $q->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators', 'logisticSupportUsers'])
            ->when($date, fn($q) => $q->where('date', '>=', $date))
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Empareja visualmente el producto con el grupo correspondiente
     */
    private function findMatchingGroup($product, Collection $managedGroups)
    {
        if (!$product) return null;
        return $managedGroups->first(function ($g) use ($product) {
            return $g->rubro_id == $product->rubro_id && $g->location_id == $product->location_id;
        });
    }

    private function injectMetadata($act, bool $isOwner, $displayId, $displayName, $ownerName, $group = null): void
    {
        $act->setAttribute('is_owner', $isOwner);
        $act->setAttribute('display_user_id', $displayId);
        $act->setAttribute('display_user_name', $displayName);
        $act->setAttribute('ownerName', $ownerName);
        $act->setAttribute('assigned_group', $group); // Inyectamos el grupo al modelo
    }

    private function getDateFilter(string $period): ?Carbon
    {
        return match($period) {
            '7days' => Carbon::now()->subDays(7),
            '15days' => Carbon::now()->subDays(15),
            default => null
        };
    }
}

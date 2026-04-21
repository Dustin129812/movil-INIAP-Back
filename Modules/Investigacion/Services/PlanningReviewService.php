<?php

namespace Modules\Investigacion\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Entities\WeekActivity;

class PlanningReviewService
{
    /**
     * Obtiene y formatea las planificaciones semanales según los grupos que gestiona el revisor.
     */
    public function getWeeklyPlanningData(User $revisor, string $period = '15days'): Collection
    {
        $managedGroupIds = $this->getManagedGroupIds($revisor);

        if ($managedGroupIds->isEmpty()) {
            return collect();
        }

        $dateFilter = $this->getDateFilter($period);
        $statuses = ['pending', 'approved', 'rejected', 'reassigned'];

        $ownActivities = $this->fetchOwnActivities($managedGroupIds, $statuses, $dateFilter);

        $ownActivities->each(fn($act) => $this->injectMetadata($act, true, $act->user_id, $act->user->name, $act->user->name));

        $supportActivities = $this->fetchSupportActivities($managedGroupIds, $statuses, $dateFilter);
        $decoratedSupport = collect();

        foreach ($supportActivities as $act) {
            foreach ($act->logisticSupportUsers as $sUser) {
                $cloned = clone $act;
                $this->injectMetadata($cloned, false, $sUser->id, $sUser->name, $act->user->name);
                $decoratedSupport->push($cloned);
            }
        }

        return $ownActivities->concat($decoratedSupport);
    }

    /**
     * Determina qué grupos puede gestionar el revisor basado en su ubicación y rol.
     */
    private function getManagedGroupIds(User $revisor): Collection
    {
        if ($revisor->location->is_central) {
            return Group::where('location_id', $revisor->location_id)
                ->where('responsible_id', $revisor->id)
                ->pluck('id');
        }

        if ($revisor->hasRole('station-director')) {
            return Group::where('location_id', $revisor->location_id)->pluck('id');
        }

        return Group::where('location_id', $revisor->location_id)
            ->where('responsible_id', $revisor->id)
            ->pluck('id');
    }

    /**
     * Obtiene las actividades basadas en los GRUPOS gestionados, no solo en los usuarios.
     */
    private function fetchOwnActivities(Collection $groupIds, array $statuses, ?Carbon $date): Collection
    {
        return WeekActivity::whereIn('status', $statuses)
            ->whereHas('activity.product', function ($q) use ($groupIds) {
                $q->whereIn('group_id', $groupIds);
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators'])
            ->when($date, fn($q) => $q->where('date', '>=', $date))
            ->orderBy('date', 'desc')
            ->get();
    }

    private function fetchSupportActivities(Collection $groupIds, array $statuses, ?Carbon $date): Collection
    {
        return WeekActivity::whereIn('status', $statuses)
            ->whereHas('activity.product', function ($q) use ($groupIds) {
                $q->whereIn('group_id', $groupIds);
            })
            ->whereHas('logisticSupportUsers', function ($q) {
                $q->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
            })
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators', 'logisticSupportUsers'])
            ->when($date, fn($q) => $q->where('date', '>=', $date))
            ->orderBy('date', 'desc')
            ->get();
    }

    private function formatGroupedData(Collection $own, Collection $support, Collection $teamIds): array
    {
        $groupedByUser = [];
        $userNames = [];

        // Inyección de atributos para Actividades Propias
        foreach ($own as $act) {
            $this->injectMetadata($act, true, $act->user_id, $act->user->name, $act->user->name);
            $groupedByUser[$act->user_id][] = $act;
            $userNames[$act->user_id] = $act->user->name;
        }

        // Inyección de atributos para Apoyos (Clonación por usuario de apoyo)
        foreach ($support as $act) {
            foreach ($act->logisticSupportUsers as $sUser) {
                if ($teamIds->contains($sUser->id)) {
                    $cloned = clone $act;
                    $this->injectMetadata($cloned, false, $sUser->id, $sUser->name, $act->user->name);
                    $groupedByUser[$sUser->id][] = $cloned;
                    $userNames[$sUser->id] = $sUser->name;
                }
            }
        }

        // Estructura final para el Frontend
        $formatted = [];
        foreach ($groupedByUser as $uId => $acts) {
            $products = collect($acts)->groupBy('activity.product_id');
            $formatted[] = [
                'id' => $uId,
                'name' => $userNames[$uId] ?? 'Usuario',
                'activities' => $products->map(fn($pActs) => [
                    'product_id' => $pActs->first()->activity->product->id,
                    'product_name' => $pActs->first()->activity->product->name,
                    'activity_description' => $pActs->first()->activity->name,
                    'week_activities' => $pActs->values(),
                ])->values()
            ];
        }

        return $formatted;
    }

    private function injectMetadata($act, bool $isOwner, $displayId, $displayName, $ownerName): void
    {
        $act->setAttribute('is_owner', $isOwner);
        $act->setAttribute('display_user_id', $displayId);
        $act->setAttribute('display_user_name', $displayName);
        $act->setAttribute('ownerName', $ownerName);
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

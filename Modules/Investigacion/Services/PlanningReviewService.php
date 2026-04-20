<?php

namespace Modules\Investigacion\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Investigacion\Entities\WeekActivity;

class PlanningReviewService
{
    /**
     * Obtiene y formatea las planificaciones semanales según el rol del revisor.
     */
    public function getWeeklyPlanningData(User $revisor, string $period = '15days'): array
    {
        $teamMemberIds = $this->getTeamMemberIds($revisor);

        if ($teamMemberIds->isEmpty()) {
            return [];
        }

        $dateFilter = $this->getDateFilter($period);
        $statuses = ['pending', 'approved', 'rejected', 'reassigned'];

        // 1. Obtener Actividades Propias y de Apoyo
        $ownActivities = $this->fetchOwnActivities($teamMemberIds, $statuses, $dateFilter);
        $supportActivities = $this->fetchSupportActivities($teamMemberIds, $statuses, $dateFilter);

        // 2. Procesar y Agrupar
        return $this->formatGroupedData($ownActivities, $supportActivities, $teamMemberIds);
    }

    /**
     * Lógica de Bypass Jerárquico: Identifica a quién debe supervisar el revisor.
     */
    private function getTeamMemberIds(User $revisor): Collection
    {
        // Si es Director de Estación, supervisa a todos los Product Managers de su localidad
        if ($revisor->hasRole('station-director')) {
            return User::role('product-manager')
                ->where('location_id', $revisor->location_id)
                ->pluck('id');
        }

        // Si es Product Manager, supervisa a los miembros de sus grupos
        $revisor->load('groups.members');
        return $revisor->groups->flatMap(fn($group) => $group->members->pluck('id'))->unique();
    }

    private function fetchOwnActivities(Collection $ids, array $statuses, ?Carbon $date): Collection
    {
        return WeekActivity::whereIn('status', $statuses)
            ->whereIn('user_id', $ids)
            ->with(['activity.product.rubro', 'activity.product.location', 'user', 'materials', 'activity.indicators'])
            ->when($date, fn($q) => $q->where('date', '>=', $date))
            ->orderBy('date', 'desc')
            ->get();
    }

    private function fetchSupportActivities(Collection $ids, array $statuses, ?Carbon $date): Collection
    {
        return WeekActivity::whereIn('status', $statuses)
            ->whereHas('logisticSupportUsers', function ($q) use ($ids) {
                $q->whereIn('users.id', $ids)
                    ->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
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

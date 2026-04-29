<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Carbon\Carbon;
use Modules\Investigacion\Entities\WeekActivity;

class WeeklyPlanDataService
{
    public function getUserPlans(User $user, array $filters)
    {
        $baseRelations = ['activity.product.rubro', 'activity.users', 'materials', 'performanceIndicators', 'logisticSupportUsers', 'user'];

        $ownQuery = WeekActivity::where('user_id', $user->id)->with($baseRelations);
        $supportQuery = WeekActivity::whereHas('logisticSupportUsers', function ($q) use ($user) {
            $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
        })->with($baseRelations);

        $this->applyFilters($ownQuery, $supportQuery, $filters);

        $ownActivities = $ownQuery->get()->map(function($act) {
            $act->setAttribute('is_owner_flag', true);
            return $act;
        });

        $supportActivities = $supportQuery->get()->map(function($act) use ($user) {
            $act->setAttribute('is_owner_flag', false);
            $act->setAttribute('supported_owner_name', $act->user->name ?? 'Compañero');
            return $act;
        });

        $weeklyPlans = $ownActivities->merge($supportActivities)->sortByDesc('date')->values();

        return $this->formatPlansForFrontend($weeklyPlans);
    }

    public function getPlansByLocation(User $authUser, array $filters)
    {
        $userIdsToQuery = isset($filters['id'])
            ? [$filters['id']]
            : User::where('location_id', $authUser->location_id)->pluck('id')->toArray();

        if (empty($userIdsToQuery)) return [];

        $baseRelations = ['activity.product.rubro', 'activity.users', 'materials', 'performanceIndicators', 'logisticSupportUsers', 'user'];

        $ownQuery = WeekActivity::whereIn('user_id', $userIdsToQuery)->with($baseRelations);
        $supportQuery = WeekActivity::whereHas('logisticSupportUsers', function ($q) use ($userIdsToQuery) {
            $q->whereIn('users.id', $userIdsToQuery)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
        })->with($baseRelations);

        $this->applyFilters($ownQuery, $supportQuery, $filters);

        $ownActivities = $ownQuery->get()->map(function($act) {
            $act->setAttribute('is_owner_flag', true);
            $act->setAttribute('display_user_name', $act->user->name ?? 'N/A');
            return $act;
        });

        $supportActivities = $supportQuery->get()->flatMap(function($act) use ($userIdsToQuery) {
            $clones = collect();
            foreach ($act->logisticSupportUsers as $supportUser) {
                if (in_array($supportUser->id, $userIdsToQuery) && in_array($supportUser->pivot->status, ['accepted', 'pending'])) {
                    $clonedAct = clone $act;
                    $clonedAct->setAttribute('is_owner_flag', false);
                    $clonedAct->setAttribute('display_user_name', $supportUser->name);
                    $clonedAct->setAttribute('supported_owner_name', $act->user->name ?? 'Compañero');
                    $clones->push($clonedAct);
                }
            }
            return $clones;
        });

        $weeklyPlans = $ownActivities->merge($supportActivities)->sortByDesc('date')->values();

        return $this->formatPlansForFrontend($weeklyPlans);
    }

    private function applyFilters($ownQuery, $supportQuery, array $filters)
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            $ownQuery->whereBetween('date', [$startDate, $endDate]);
            $supportQuery->whereBetween('date', [$startDate, $endDate]);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statuses = explode(',', $filters['status']);
            $ownQuery->whereIn('status', $statuses);
            $supportQuery->whereIn('status', $statuses);
        } else {
            $allVisibleStatuses = ['approved', 'rated', 'reassigned', 'in progress', 'pending', 'completed', 'partial', 'not completed'];
            $ownQuery->whereIn('status', $allVisibleStatuses);
            $supportQuery->whereIn('status', $allVisibleStatuses);
        }
    }

    private function formatPlansForFrontend($weeklyPlans)
    {
        return $weeklyPlans->map(function ($plan) {
            $productInitialCode = ''; $activityInitialCode = ''; $combinedCodePrefix = '';
            if ($plan->activity && $plan->activity->product && !empty($plan->activity->product->name)) $productInitialCode = strtoupper(substr($plan->activity->product->name, 0, 2));
            if ($plan->activity && !empty($plan->activity->description)) $activityInitialCode = strtoupper(substr($plan->activity->description, 0, 2));

            if (!empty($productInitialCode) && !empty($activityInitialCode)) $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
            elseif (!empty($productInitialCode)) $combinedCodePrefix = $productInitialCode . ': ';
            elseif (!empty($activityInitialCode)) $combinedCodePrefix = $activityInitialCode . ': ';

            return [
                'id' => $plan->id,
                'date' => Carbon::parse($plan->date)->isoFormat('dddd, D [de] MMMM [de]YYYY'),
                'description' => $combinedCodePrefix . ($plan->description ?? 'N/A'),
                'estimated_hours' => $plan->estimated_hours,
                'work_location' => $plan->work_location,
                'observations' => $plan->observations,
                'status' => $plan->status,
                'activity_base_id' => $plan->activity->id ?? null,
                'product_name' => $plan->activity->product->name ?? 'N/A',
                'rubro_name' => $plan->activity->product->rubro->name ?? 'N/A',
                'responsables' => $plan->activity->users->pluck('name')->implode(', ') ?? 'N/A',

                'user_name' => $plan->display_user_name ?? ($plan->user->name ?? 'N/A'),
                'is_owner' => $plan->is_owner_flag ?? true,
                'owner_name' => $plan->supported_owner_name ?? null,

                'materials' => $plan->materials->map(fn($material) => [
                    'name' => $material->name, 'quantity' => $material->pivot->quantity, 'description' => $material->pivot->description,
                ]),
                'indicators' => $plan->performanceIndicators->pluck('name')->implode(' - '),
                'logistic_supports' => $plan->logisticSupportUsers->map(fn($user) => ['id' => $user->id, 'name' => $user->name])->toArray(),
            ];
        });
    }
}

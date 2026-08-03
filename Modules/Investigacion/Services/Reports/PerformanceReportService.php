<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeeklyPulse;

class PerformanceReportService
{
    public function generateUserDeepDivePdf(array $validatedData, User $user)
    {
        $startDate = $validatedData['start_date'];
        $endDate = $validatedData['end_date'];

        $allActivities = $this->getBaseActivitiesQuery($user, $startDate, $endDate)->get();
        $groupedActivities = $allActivities->groupBy(fn($activity) => Carbon::parse($activity->date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'));

        $data = [
            'reportDate' => Carbon::now()->locale('es')->isoFormat('LL'),
            'user' => $user,
            'startDate' => Carbon::parse($startDate)->locale('es')->isoFormat('LL'),
            'endDate' => Carbon::parse($endDate)->locale('es')->isoFormat('LL'),
            'performanceStats' => $this->getPerformanceStatsForUser($user, $startDate, $endDate),
            'weeklyLoadChart' => $this->getWeeklyLoadForUser($user, $startDate, $endDate),
            'pulseHistory' => $this->getPulseHistoryForUser($user, $startDate, $endDate),
            'collaborationStats' => $this->getCollaborationStatsForUser($user, $startDate, $endDate),
            'groupedActivities' => $groupedActivities,
        ];

        return Pdf::loadView('reports.user_deep_dive_report', $data)->download('informe-detallado-' . Str::slug($user->name) . '.pdf');
    }

    public function getUserDeepDiveData(array $validatedData, User $user)
    {
        $startDate = $validatedData['start_date'];
        $endDate = $validatedData['end_date'];

        return [
            'performanceStats' => $this->getPerformanceStatsForUser($user, $startDate, $endDate),
            'productBreakdown' => $this->getProductBreakdownForUser($user, $startDate, $endDate),
            'weeklyLoadChart' => $this->getWeeklyLoadForUser($user, $startDate, $endDate),
            'pulseHistory' => $this->getPulseHistoryForUser($user, $startDate, $endDate),
            'collaborationStats' => $this->getCollaborationStatsForUser($user, $startDate, $endDate),
            'allActivities' => $this->getBaseActivitiesQuery($user, $startDate, $endDate)->get(),
        ];
    }

    public function generateRubroDeepDivePdf(Rubro $rubro, User $authUser)
    {
        $rubro->load([
            'groups' => function ($query) use ($authUser) { $query->where('location_id', $authUser->location_id); },
            'products' => function ($query) use ($authUser) {
                $query->where('location_id', $authUser->location_id)
                    ->with(['activities.weeklyActivities' => function ($q) { $q->with('user:id,name')->orderBy('date', 'desc'); }]);
            }
        ]);

        $reportData = [
            'rubro' => [
                'name' => $rubro->name,
                'total_budget' => $rubro->products->sum('budget'),
                'groups' => $rubro->groups->toArray(),
                'products' => $rubro->products->toArray(),
            ]
        ];

        return Pdf::loadView('reports.rubro_deep_dive_report', $reportData)->download('informe_detallado_' . Str::slug($rubro->name) . '.pdf');
    }

    // --- MÉTODOS PRIVADOS EXTRAÍDOS DEL CONTROLADOR ---

    private function getBaseActivitiesQuery(User $user, $startDate, $endDate)
    {
        return WeekActivity::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('logisticSupportUsers', function ($q) use ($user) {
                    $q->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                });
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated']);
    }

    private function getPerformanceStatsForUser(User $user, $startDate, $endDate)
    {
        return $this->getBaseActivitiesQuery($user, $startDate, $endDate)
            ->select(
                DB::raw("COUNT(*) as total_activities"),
                DB::raw("COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed"),
                DB::raw("COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as partial"),
                DB::raw("COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_completed"),
                DB::raw("AVG(percentage) as average_compliance")
            )->first()->toArray();
    }

    private function getProductBreakdownForUser(User $user, $startDate, $endDate)
    {
        return Product::whereHas('activities.users', fn($q) => $q->where('users.id', $user->id))
            ->with(['activities' => function ($query) use ($user, $startDate, $endDate) {
                $query->whereHas('users', fn($q) => $q->where('users.id', $user->id))
                    ->with(['weeklyActivities' => function($q) use ($user, $startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate])
                            ->where(function($q2) use ($user) {
                                $q2->where('user_id', $user->id)->orWhereHas('logisticSupportUsers', function ($q3) use ($user) {
                                    $q3->where('users.id', $user->id)->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                                });
                            });
                    }]);
            }])->get();
    }

    private function getWeeklyLoadForUser(User $user, $startDate, $endDate)
    {
        $weeks = $this->getBaseActivitiesQuery($user, $startDate, $endDate)
            ->select(
                DB::raw("DATE_TRUNC('week', date) AS week_start"),
                DB::raw("COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed"),
                DB::raw("COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as partial"),
                DB::raw("COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_completed")
            )
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get();

        return $weeks->map(fn($week) => [
            'week' => 'Sem. ' . Carbon::parse($week->week_start)->format('W'),
            'completed' => (int) $week->completed,
            'partial' => (int) $week->partial,
            'not_completed' => (int) $week->not_completed,
        ]);
    }

    private function getPulseHistoryForUser(User $user, $startDate, $endDate)
    {
        return WeeklyPulse::where('user_id', $user->id)
            ->whereBetween('week_start_date', [Carbon::parse($startDate)->startOfWeek(), Carbon::parse($endDate)->endOfWeek()])
            ->orderBy('week_start_date', 'desc')
            ->select('week_start_date', 'status', 'comment')
            ->get();
    }

    private function getCollaborationStatsForUser(User $user, $startDate, $endDate)
    {
        $activities = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('logisticSupportUsers')->get();

        $supportGiven = DB::table('week_activity_logistic_support_user')
            ->join('weekly_activities', 'weekly_activities.id', '=', 'week_activity_logistic_support_user.weekly_activity_id')
            ->join('users', 'users.id', '=', 'weekly_activities.user_id')
            ->where('week_activity_logistic_support_user.user_id', $user->id)
            ->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending'])
            ->whereBetween('weekly_activities.date', [$startDate, $endDate])
            ->select('users.name')
            ->get()->countBy('name');

        return [
            'support_requested' => $activities->flatMap->logisticSupportUsers->countBy('name'),
            'support_given' => $supportGiven
        ];
    }
}

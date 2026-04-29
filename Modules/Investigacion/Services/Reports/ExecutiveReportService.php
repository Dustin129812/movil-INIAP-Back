<?php

namespace Modules\Investigacion\Services\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeeklyPulse;
use Modules\Investigacion\Http\Controllers\Traits\CalculatesProgress;

class ExecutiveReportService
{
    use CalculatesProgress;

    public function generateNationalExecutiveSummary()
    {
        $locations = Location::all();
        $allProducts = Product::with(['activities.monthlyExecutionProgress'])->get();
        $allUsers = User::whereHas('roles', fn($q) => $q->where('name', 'researcher'))->get();
        $officialRubroId = Rubro::where('name', 'OFICIAL')->value('id');

        $productsByLocation = $allProducts->groupBy('location_id');
        $usersByLocation = $allUsers->groupBy('location_id');

        $detailedStationData = $locations->map(function ($location) use ($productsByLocation, $usersByLocation, $officialRubroId) {
            $stationId = $location->id;
            $locationProducts = $productsByLocation->get($stationId) ?? collect();
            $locationUsers = $usersByLocation->get($stationId) ?? collect();

            $poaProducts = $locationProducts->where('rubro_id', '!=', $officialRubroId);
            $progress = $this->calculateTotalProgress($poaProducts);
            $totalBudget = $locationProducts->sum('budget');
            $recentDate = Carbon::now()->subDays(30);

            $activeProjectsCount = Product::where('location_id', $stationId)
                ->whereHas('activities.weeklyActivities', fn($q) => $q->where('date', '>=', $recentDate))->count();

            $fourWeeksAgo = Carbon::now()->subWeeks(4)->startOfWeek();
            $recentProgress = WeekActivity::whereIn('user_id', $locationUsers->pluck('id'))->where('date', '>=', $fourWeeksAgo)->avg('percentage');

            return [
                'name' => $location->name,
                'poa_progress' => round($progress * 100, 2),
                'total_budget' => $totalBudget,
                'project_count' => $locationProducts->count(),
                'active_projects_count' => $activeProjectsCount,
                'researcher_count' => $locationUsers->count(),
                'monthly_progress_estimate' => round($recentProgress, 2) ?: 0,
                'researchers' => $locationUsers->pluck('name')->toArray(),
            ];
        });

        $kpis = [
            'poa_progress' => round($detailedStationData->avg('poa_progress'), 2),
            'total_budget' => $detailedStationData->sum('total_budget'),
            'total_projects' => $detailedStationData->sum('project_count'),
            'total_researchers' => $detailedStationData->sum('researcher_count'),
            'active_stations' => $locations->count(),
        ];

        return Pdf::loadView('reports.national_executive_summary', ['kpis' => $kpis, 'stationData' => $detailedStationData->sortByDesc('poa_progress')->values()])
            ->setPaper('a4', 'portrait')->download('informe_situacion_nacional.pdf');
    }

    public function generateStationComparisonReport(array $performanceData)
    {
        // Se asume que el array $performanceData ya fue resuelto por el NationalDashboardService/Controller y pasado aquí
        $performanceCollection = collect($performanceData);
        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();

        $enrichedData = $performanceCollection->map(function ($stationData) use ($lastWeekStartDate) {
            $stationId = $stationData['location_id'];
            $stationData['total_budget'] = Product::where('location_id', $stationId)->sum('budget');

            $memberIds = User::where('location_id', $stationId)->pluck('id');
            $pulses = WeeklyPulse::whereIn('user_id', $memberIds)->where('week_start_date', $lastWeekStartDate->toDateString())->get();

            if ($pulses->isEmpty() || $memberIds->isEmpty()) {
                $stationData['average_pulse_score'] = 0;
            } else {
                $pulseScoreMap = ['green' => 3, 'yellow' => 2, 'red' => 1];
                $totalScore = $pulses->reduce(fn($sum, $pulse) => $sum + ($pulseScoreMap[$pulse->status] ?? 0), 0);
                $stationData['average_pulse_score'] = $totalScore / $memberIds->count();
            }

            return $stationData;
        });

        $sortedData = $enrichedData->sortByDesc('poa_progress')->values();

        $dataForView = [
            'performanceData' => $sortedData,
            'topPerformer' => $sortedData->first(),
            'lowPerformer' => $sortedData->last(),
            'pulseAlert' => $sortedData->where('average_pulse_score', '>', 0)->sortBy('average_pulse_score')->first(),
        ];

        return Pdf::loadView('reports.station_comparison_report', $dataForView)->download('reporte_comparativo_estaciones.pdf');
    }
}

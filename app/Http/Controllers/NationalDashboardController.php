<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\CalculatesProgress;
use App\Models\Product;
use App\Models\Rubro;
use App\Models\User;
use App\Models\Location;
use App\Models\WeekActivity;
use App\Models\WeeklyPulse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NationalDashboardController extends Controller
{
    use CalculatesProgress;

    /**
     * Obtiene los Indicadores Clave de Rendimiento (KPIs) a nivel nacional.
     */
    public function getNationalKpis(Request $request)
    {
        // Usamos ->all() para no filtrar por ubicación
        $allProducts = Product::with(['activities.monthlyExecutionProgress'])->get();
        $officialRubroId = Rubro::where('name', 'OFICIAL')->value('id');

        // Separamos productos
        $poaProducts = $allProducts->where('rubro_id', '!=', $officialRubroId);

        // Usamos la función del Trait para calcular el progreso
        $poaProgress = $this->calculateTotalProgress($poaProducts);

        // Calculamos los demás KPIs nacionales
        $totalBudget = $allProducts->sum('budget');
        $totalProjects = $allProducts->count();
        $totalResearchers = User::count();
        $activeStations = Location::count();

        return response()->json([
            'data' => [
                'poa_progress' => round($poaProgress * 100, 2),
                'total_budget' => $totalBudget,
                'total_projects' => $totalProjects,
                'total_researchers' => $totalResearchers,
                'active_stations' => $activeStations,
            ]
        ]);
    }

    /**
     * Obtiene y compara el rendimiento de todas las estaciones.
     */
    public function getStationPerformance(Request $request)
    {
        // 1. Cargamos todos los datos necesarios en pocas consultas
        $locations = Location::all();
        $allProducts = Product::with(['activities.monthlyExecutionProgress'])->get();
        $allUsers = User::all(); // Podemos refinar por rol si es necesario
        $officialRubroId = Rubro::where('name', 'OFICIAL')->value('id');

        // 2. Agrupamos los datos por ubicación para un acceso rápido
        $productsByLocation = $allProducts->groupBy('location_id');
        $usersByLocation = $allUsers->groupBy('location_id');

        // 3. Mapeamos cada ubicación para construir su resumen de rendimiento
        $performanceData = $locations->map(function ($location) use ($productsByLocation, $usersByLocation, $officialRubroId) {

            // Obtenemos los productos y usuarios para ESTA ubicación
            $locationProducts = $productsByLocation->get($location->id) ?? collect();
            $locationUsers = $usersByLocation->get($location->id) ?? collect();

            // Filtramos solo los productos POA para el cálculo de progreso
            $locationPoaProducts = $locationProducts->where('rubro_id', '!=', $officialRubroId);

            // Usamos nuestro Trait para calcular el progreso de la estación
            $progress = $this->calculateTotalProgress($locationPoaProducts);

            return [
                'location_id' => $location->id,
                'location_name' => $location->name,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'poa_progress' => round($progress * 100, 2),
                'project_count' => $locationProducts->count(),
                'researcher_count' => $locationUsers->count(),
            ];
        });

        // Ordenamos los resultados para mostrar las estaciones de mayor a menor progreso
        $sortedPerformanceData = $performanceData->sortByDesc('poa_progress')->values();

        return response()->json(['data' => $sortedPerformanceData]);
    }

    /**
     * Obtiene un resumen del pulso semanal de todos los usuarios a nivel nacional.
     */
    public function getNationalPulseSummary(Request $request)
    {
        // 1. Obtenemos todos los usuarios de la organización
        $allUsers = User::all();
        if ($allUsers->isEmpty()) {
            return response()->json(['data' => ['summary' => null]]);
        }

        $allUserIds = $allUsers->pluck('id');

        // 2. Buscamos los pulsos reportados en la última semana completada
        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();
        $pulses = WeeklyPulse::whereIn('user_id', $allUserIds)
            ->where('week_start_date', $lastWeekStartDate->toDateString())
            ->get()->keyBy('user_id');

        // 3. Asignamos un estado a cada usuario (reportado o 'gray')
        $nationalPulseData = $allUsers->map(function ($user) use ($pulses) {
            $pulse = $pulses->get($user->id);
            return ['status' => $pulse->status ?? 'gray'];
        });

        // 4. Calculamos el resumen nacional
        $counts = $nationalPulseData->countBy('status');
        $total = $allUsers->count();

        $summary = [
            'total' => $total,
            'counts' => [
                'green' => $counts->get('green', 0),
                'yellow' => $counts->get('yellow', 0),
                'red' => $counts->get('red', 0),
                'gray' => $counts->get('gray', 0),
            ],
            'percentages' => [
                'green' => $total > 0 ? round(($counts->get('green', 0) / $total) * 100) : 0,
                'yellow' => $total > 0 ? round(($counts->get('yellow', 0) / $total) * 100) : 0,
                'red' => $total > 0 ? round(($counts->get('red', 0) / $total) * 100) : 0,
                'gray' => $total > 0 ? round(($counts->get('gray', 0) / $total) * 100) : 0,
            ]
        ];

        return response()->json(['data' => ['summary' => $summary]]);
    }

    /**
     * Obtiene el rendimiento consolidado por cada Rubro a nivel nacional.
     */
    public function getNationalRubroPerformance(Request $request)
    {
        // 1. Obtenemos TODAS las actividades finalizadas en el país en la última semana
        //    (hacemos el rango de fechas opcional, con un default de la última semana)
        $endDate = Carbon::now()->endOfWeek();
        $startDate = Carbon::now()->subDays(7)->startOfWeek();

        $activities = WeekActivity::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->with('activity.product.rubro') // Cargamos la relación clave
            ->get();

        // 2. Agrupamos las actividades por el ID del rubro
        $groupedByRubro = $activities->groupBy('activity.product.rubro.id');

        $performanceData = [];

        // 3. Iteramos y calculamos las estadísticas para cada rubro
        foreach ($groupedByRubro as $rubroId => $rubroActivities) {
            if (empty($rubroId) || $rubroActivities->isEmpty()) {
                continue;
            }

            $rubroName = $rubroActivities->first()->activity->product->rubro->name;

            $performanceData[] = [
                'rubro_id' => $rubroId,
                'rubro_name' => $rubroName,
                'average_compliance' => round($rubroActivities->avg('percentage'), 2),
                'total_activities' => $rubroActivities->count(),
            ];
        }

        // Ordenamos por cumplimiento para que el gráfico se vea bien
        $sortedPerformanceData = collect($performanceData)->sortByDesc('average_compliance')->values();

        return response()->json(['data' => $sortedPerformanceData]);
    }
}

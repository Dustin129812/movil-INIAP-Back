<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Group;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeeklyPulse;
use Modules\Investigacion\Http\Controllers\Traits\CalculatesProgress;

class EstacionDashboardController extends Controller
{

    use CalculatesProgress;

    /**
     * Obtiene los Indicadores Clave de Rendimiento (KPIs) para la estación del director.
     */
    public function getKpis(Request $request)
    {
        $director = Auth::user();
        $locationId = $director->location_id;

        if (!$locationId) {
            return response()->json(['error' => 'El usuario no tiene una ubicación asignada.'], 400);
        }

        // Obtenemos el ID del rubro 'OFICIAL' para separar los cálculos
        $officialRubroId = Rubro::where('name', 'OFICIAL')->value('id');

        // Reutilizamos la lógica de carga de PlannerController
        $productsInLocation = Product::where('location_id', $locationId)
            ->with(['activities.monthlyExecutionProgress'])
            ->get();

        // Separamos los productos en POA y Extra-POA
        $poaProducts = $productsInLocation->where('rubro_id', '!=', $officialRubroId);
        $extraPoaProducts = $productsInLocation->where('rubro_id', $officialRubroId);

        // Calculamos el progreso para cada grupo
        $poaProgress = $this->calculateTotalProgress($poaProducts);
        $extraPoaProgress = $this->calculateTotalProgress($extraPoaProducts);

        // Obtenemos los conteos adicionales
        $activeProjectsCount = $productsInLocation->count();
        $teamMembersCount = User::where('location_id', $locationId)->count();

        return response()->json([
            'data' => [
                'poa_progress' => round($poaProgress * 100, 2),
                'extra_poa_progress' => round($extraPoaProgress * 100, 2),
                'active_projects_count' => $activeProjectsCount,
                'team_members_count' => $teamMembersCount,
            ]
        ]);
    }

    /**
     * Obtiene el rendimiento de todos los grupos de la estación.
     */
    public function getGroupPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $director = Auth::user();
        $locationId = $director->location_id;

        $groups = Group::where('location_id', $locationId)->with('members')->get();

        if ($groups->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $performanceData = [];

        foreach ($groups as $group) {
            $memberIds = $group->members->pluck('id');

            if ($memberIds->isEmpty()) {
                continue;
            }

            $stats = WeekActivity::whereIn('user_id', $memberIds)
                ->whereBetween('date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
                ->select(
                    DB::raw("COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed_count"),
                    DB::raw("COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as partial_count"),
                    DB::raw("COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_completed_count"),
                    DB::raw("AVG(percentage) as average_compliance")
                )
                ->first();

            $performanceData[] = [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'completed_count' => (int) $stats->completed_count,
                'partial_count' => (int) $stats->partial_count,
                'not_completed_count' => (int) $stats->not_completed_count,
                'average_compliance' => round((float) $stats->average_compliance, 2),
            ];
        }

        return response()->json(['data' => $performanceData]);
    }

    public function getTeamPulse(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $director = Auth::user();
        $locationId = $director->location_id;
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $teamMembers = User::where('location_id', $locationId)->get();

        if ($teamMembers->isEmpty()) {
            return response()->json(['data' => ['teamPulseData' => [], 'summary' => null]]);
        }

        $teamMemberIds = $teamMembers->pluck('id');

        // Obtenemos TODOS los pulsos dentro del rango de fechas
        $pulsesInRange = WeeklyPulse::whereIn('user_id', $teamMemberIds)
            ->whereBetween('week_start_date', [$startDate, $endDate])
            ->orderBy('week_start_date', 'desc') // Ordenamos para que el más reciente sea el primero
            ->get();

        // Para cada usuario, nos quedamos solo con su pulso más reciente en el rango
        $latestPulses = $pulsesInRange->unique('user_id');
        $pulsesKeyedByUser = $latestPulses->keyBy('user_id');

        // Mapeamos los datos para cada miembro
        $teamPulseData = $teamMembers->map(function ($member) use ($pulsesKeyedByUser) {
            $pulse = $pulsesKeyedByUser->get($member->id);
            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'status' => $pulse->status ?? 'gray',
                'comment' => $pulse->comment ?? 'No reportado',
            ];
        });

        // El cálculo del resumen funciona igual, pero ahora con los datos del rango
        $counts = $teamPulseData->countBy('status');
        $total = $teamMembers->count();
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

        return response()->json([
            'data' => [
                'teamPulseData' => $teamPulseData,
                'summary' => $summary,
            ]
        ]);
    }


    public function getRubroDeepDive(Request $request, Rubro $rubro)
    {
        // Cargamos todas las relaciones necesarias de forma eficiente
        $rubro->load([
            // Grupos que pertenecen a este rubro y a la estación del director
            'groups' => function ($query) {
                $query->where('location_id', Auth::user()->location_id);
            },
            // Productos del rubro en la estación, con sus actividades y planificaciones
            'products' => function ($query) {
                $query->where('location_id', Auth::user()->location_id)
                    ->with([
                        'activities.weeklyActivities' => function ($q) {
                            $q->select('id', 'activity_id', 'description', 'date', 'user_id', 'status')
                                ->with('user:id,name') // Solo traemos id y nombre del usuario
                                ->orderBy('date', 'desc');
                        }
                    ]);
            }
        ]);

        // Calculamos el presupuesto total del rubro sumando el de sus productos
        $totalBudget = $rubro->products->sum('budget');

        // Estructuramos la respuesta
        return response()->json([
            'data' => [
                'id' => $rubro->id,
                'name' => $rubro->name,
                'total_budget' => $totalBudget,
                'groups' => $rubro->groups->map(fn($g) => ['id' => $g->id, 'name' => $g->name]),
                'products' => $rubro->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'activities' => $product->activities->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'description' => $activity->description,
                                'weekly_activities' => $activity->weeklyActivities,
                            ];
                        })
                    ];
                })
            ]
        ]);
    }

    public function getRubroPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $director = Auth::user();
        $locationId = $director->location_id;

        $activities = WeekActivity::whereHas('user', fn($q) => $q->where('location_id', $locationId))
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->with('activity.product.rubro') // Cargamos la relación anidada
            ->get();

        $groupedByRubro = $activities->groupBy('activity.product.rubro.id');

        $performanceData = [];

        foreach ($groupedByRubro as $rubroId => $rubroActivities) {
            if (empty($rubroId) || $rubroActivities->isEmpty()) {
                continue;
            }

            $firstActivity = $rubroActivities->first();
            $rubroName = $firstActivity->activity->product->rubro->name;

            $performanceData[] = [
                'rubro_id' => $rubroId,
                'rubro_name' => $rubroName,
                'completed_count' => $rubroActivities->where('percentage', 100)->count(),
                'partial_count' => $rubroActivities->where('percentage', '>', 0)->where('percentage', '<', 100)->count(),
                'not_completed_count' => $rubroActivities->where('percentage', 0)->count(),
                'average_compliance' => round($rubroActivities->avg('percentage'), 2),
            ];
        }

        return response()->json(['data' => $performanceData]);
    }
}

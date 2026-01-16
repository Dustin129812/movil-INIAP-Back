<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeeklyPulse;

class DashboardController extends Controller
{


    /**
     * Obtiene los datos para el dashboard del rol 'researcher'.
     */
    public function getResearcherDashboardData(Request $request)
    {
        $user = $request->user();

        // Widget "Mi Semana": Contar actividades planificadas para la semana actual
        $currentWeekStart = Carbon::now()->startOfWeek()->toDateString();
        $currentWeekEnd = Carbon::now()->endOfWeek()->toDateString();

        $weeklyActivitiesCount = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$currentWeekStart, $currentWeekEnd])
            ->count();

        // Widget "Mis Proyectos": Obtener los 3 proyectos más relevantes
        $myProjects = Product::where('user_id', $user->id)
            ->orWhereHas('activities.users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with('activities.weeklyActivities') // Cargar para calcular progreso
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        // Calcular el progreso de cada proyecto
        $projectsWithProgress = $myProjects->map(function ($product) {
            $totalActivities = $product->activities->count();
            if ($totalActivities === 0) {
                $progress = 0;
            } else {
                $activitiesWithProgress = $product->activities->filter(function ($activity) {
                    return $activity->weeklyActivities->isNotEmpty();
                })->count();
                $progress = round(($activitiesWithProgress / $totalActivities) * 100);
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'progress' => $progress,
            ];
        });

        return response()->json([
            'myWeek' => [
                'weeklyActivitiesCount' => $weeklyActivitiesCount,
            ],
            'myProjects' => $projectsWithProgress,
        ]);
    }

    /**
     * Obtiene los datos para el dashboard del rol 'product-manager'.
     */
    public function getProductManagerDashboardData(Request $request)
    {
        $manager = $request->user();
        $manager->load('groups.members'); // Cargar grupos y sus miembros

        // Obtener los IDs de todos los miembros en los grupos que el manager lidera
        $teamMemberIds = $manager->groups->flatMap(function ($group) {
            return $group->members->pluck('id');
        })->unique();

        // Widget "Planificaciones por Revisar"
        $pendingReviews = WeekActivity::whereIn('user_id', $teamMemberIds)
            ->where('status', 'pending')
            ->with('user:id,name') // Cargar solo id y nombre del usuario
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('user_id') // Obtener solo una entrada por usuario
            ->map(fn ($activity) => [
                'id' => $activity->user->id,
                'userName' => $activity->user->name,
                'submissionDate' => Carbon::parse($activity->created_at)->toDateString(),
            ]);

        // Widget "Pulso del Equipo"
        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();
        $teamMembers = User::whereIn('id', $teamMemberIds)->with(['weeklyPulses' => function ($query) {
            $query->orderBy('week_start_date', 'desc')->limit(4); // Cargar los últimos 4 pulsos
        }])->get();

        $teamPulse = $teamMembers->map(function ($member) use ($lastWeekStartDate) {
            $latestPulse = $member->weeklyPulses->where('week_start_date', $lastWeekStartDate->toDateString())->first();
            return [
                'id' => $member->id,
                'name' => $member->name,
                'status' => $latestPulse->status ?? 'gray', // 'gray' si no hay reporte
                'trend' => $member->weeklyPulses->pluck('status')->toArray(),
                'hasComment' => !empty($latestPulse->comment),
            ];
        });

        // Widget "Métricas Clave"
        $stats = [
            'activeProjects' => Product::whereIn('user_id', $teamMemberIds)->count(),
            'pendingActivities' => $pendingReviews->count(),
            'overdueActivities' => 0,
            'teamMembers' => $teamMemberIds->count(),
        ];


        return response()->json([
            'reviewQueue' => $pendingReviews,
            'teamPulse' => $teamPulse,
            'programStats' => $stats,
        ]);
    }

    public function getPortfolioProgress(Request $request)
    {
        try {
            $user = Auth::user();

            $products = Product::where('location_id', $user->location_id)
                ->with([
                    'rubro',
                    'user',
                    'activities' => function ($query) {
                        $query->with(['users', 'monthlyExecutionProgress']);
                    },
                ])->get();

            $formattedProducts = $products->map(function ($product) {
                $productAbsoluteWeight = (float) $product->ponderacion / 100;

                $mappedActivities = ($product->activities ?? collect([]))->map(function ($activity) use ($productAbsoluteWeight) {
                    $activityAbsoluteWeight = $productAbsoluteWeight * ((float) $activity->ponderacion / 100);
                    $totalExecutedPercentage = $activity->monthlyExecutionProgress->sum('percentage');
                    $totalActivityProgress = $activityAbsoluteWeight * ($totalExecutedPercentage / 100);

                    return [
                        'total_progress' => $totalActivityProgress,
                    ];
                });

                $totalProductProgress = $mappedActivities->sum('total_progress');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'absolute_weight' => $productAbsoluteWeight,
                    'total_progress' => $totalProductProgress,
                    'user' => $product->user ? ['id' => $product->user->id, 'name' => $product->user->name ?? 'N/A'] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                ];
            });

            $totalRubroProgress = $formattedProducts->sum('total_progress');

            return response()->json([
                'msg' => ['summary' => 'Éxito', 'detail' => 'Reporte de portafolio obtenido correctamente.', 'code' => 200],
                'data' => [
                    'total_rubro_progress' => $totalRubroProgress,
                    'products' => $formattedProducts->values()->toArray(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'msg' => ['summary' => 'Error', 'detail' => 'No se pudo generar el reporte de portafolio.', 'code' => 500]
            ], 500);
        }
    }

    public function getTeamPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $manager = Auth::user();
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $teamMemberIds = $manager->groups()->with('members')->get()
            ->flatMap(fn($group) => $group->members->pluck('id'))
            ->unique();

        if ($teamMemberIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $performanceData = WeekActivity::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated']) // Estados que cuentan como finalizados
            ->join('users', 'weekly_activities.user_id', '=', 'users.id')
            ->select(
                'user_id',
                'users.name as user_name',
                DB::raw("COUNT(CASE WHEN weekly_activities.percentage = 100 THEN 1 END) as completed_count"),
                DB::raw("COUNT(CASE WHEN weekly_activities.percentage > 0 AND weekly_activities.percentage < 100 THEN 1 END) as partial_count"),
                DB::raw("COUNT(CASE WHEN weekly_activities.percentage = 0 THEN 1 END) as not_completed_count"),
                DB::raw("AVG(weekly_activities.percentage) as average_compliance")
            )
            ->groupBy('user_id', 'users.name')
            ->get();

        return response()->json(['data' => $performanceData]);
    }

    /**
     * Devuelve los datos para el widget de "Planificaciones por Revisar".
     */
    public function getReviewQueue(Request $request)
    {
        $manager = Auth::user();
        $manager->load('groups.members');
        $teamMemberIds = $manager->groups->flatMap(fn($group) => $group->members->pluck('id'))->unique();

        $pendingActivities = WeekActivity::whereIn('user_id', $teamMemberIds)
            ->where('status', 'pending')
            ->with('user:id,name')
            ->latest()->take(5)->get();

        $reviewQueue = $pendingActivities->map(fn($activity) => [
            'id' => $activity->id,
            'userName' => $activity->user->name,
            'submissionDate' => Carbon::parse($activity->created_at)->diffForHumans(),
        ]);

        return response()->json(['data' => $reviewQueue]);
    }

    /**
     * Devuelve los datos para el widget de "Pulso del Equipo".
     */
    public function getTeamPulseData(Request $request)
    {
        $manager = Auth::user();
        $manager->load('groups.members');
        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $teamMemberIds = $manager->groups->flatMap(fn($group) => $group->members->pluck('id'))->unique();
        $teamMembers = User::whereIn('id', $teamMemberIds)->get();

        if ($teamMembers->isEmpty()) {
            return response()->json(['data' => ['teamPulseData' => [], 'summary' => null]]);
        }

        $pulses = WeeklyPulse::whereIn('user_id', $teamMemberIds)
            ->where('week_start_date', $startDate->toDateString())
            ->get()->keyBy('user_id');

        $teamPulseData = $teamMembers->map(function ($member) use ($pulses) {
            $pulse = $pulses->get($member->id);
            return [
                'name' => $member->name,
                'status' => $pulse->status ?? 'gray',
                'comment' => $pulse->comment ?? 'No reportado',
            ];
        });

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

    /**
     * Obtiene las estadísticas de rendimiento para el usuario autenticado.
     */
    public function getMyPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = Auth::user();

        $performanceData = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->select(
                DB::raw("COUNT(CASE WHEN percentage = 100 THEN 1 END) as completed_count"),
                DB::raw("COUNT(CASE WHEN percentage > 0 AND percentage < 100 THEN 1 END) as partial_count"),
                DB::raw("COUNT(CASE WHEN percentage = 0 THEN 1 END) as not_completed_count"),
                DB::raw("AVG(percentage) as average_compliance")
            )
            ->first();

        return response()->json(['data' => $performanceData]);
    }

    /**
     * Obtiene el historial de pulso para el usuario autenticado.
     */
    public function getMyPulseHistory(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = Auth::user();

        $pulseHistory = WeeklyPulse::where('user_id', $user->id)
            ->whereBetween('week_start_date', [$request->start_date, $request->end_date])
            ->orderBy('week_start_date', 'desc')
            ->select('week_start_date', 'status', 'comment')
            ->get();

        return response()->json(['data' => $pulseHistory]);
    }
}

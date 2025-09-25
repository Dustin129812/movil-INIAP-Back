<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\CalculatesProgress;
use App\Models\Location;
use App\Models\Product;
use App\Models\Rubro;
use App\Models\User;
use App\Models\WeekActivity;
use App\Models\WeeklyPulse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportController extends Controller
{

    use CalculatesProgress;
    public function generateWeeklyPlanReport(Request $request)
    {
        Carbon::setLocale('es');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $iniap_logo_path = public_path('storage/images/iniap_logo.png');
        $ecuador_shield_path = public_path('storage/images/ecuador_shield.jpg');

        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $technician = User::with('location')->find($userId);
        if (!$technician) {
            return response()->json(['error' => 'Técnico no encontrado.'], 404);
        }

        $ratedStatuses = ['approved'];

        $weekActivities = WeekActivity::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', $ratedStatuses)
            ->with([
                'activity.product.rubro',
                'activity.users',
                'materials',
                'performanceIndicators',
                'logisticSupportUsers'
            ])
            ->orderBy('date')
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $weekActivities->each(function ($dayActivities) {
            $dayActivities->each(function ($weekActivity) {
                $productInitialCode = ''; // Renombrado para claridad
                $activityInitialCode = ''; // Renombrado para claridad
                $combinedCodePrefix = ''; // Nuevo para el código combinado

                if ($weekActivity->activity && $weekActivity->activity->product && !empty($weekActivity->activity->product->name)) {
                    $productInitialCode = strtoupper(substr($weekActivity->activity->product->name, 0, 2));
                }

                if ($weekActivity->activity && !empty($weekActivity->activity->description)) {
                    $activityInitialCode = strtoupper(substr($weekActivity->activity->description, 0, 2));
                }

                if (!empty($productInitialCode) && !empty($activityInitialCode)) {
                    $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
                } elseif (!empty($productInitialCode)) { // Si solo hay código de producto
                    $combinedCodePrefix = $productInitialCode . ': ';
                } elseif (!empty($activityInitialCode)) { // Si solo hay código de actividad
                    $combinedCodePrefix = $activityInitialCode . ': ';
                }
                $weekActivity->formatted_description = $combinedCodePrefix . ($weekActivity->description ?? '');
            });
        });

        $totalActivities = $weekActivities->count();
        $summary = [
            'completed' => $weekActivities->where('status', 'completed')->count(),
            'partial' => $weekActivities->where('status', 'partial')->count(),
            'not_done' => $weekActivities->whereIn('status', ['rated', 'not completed'])->count(),
            'overall_compliance' => ($totalActivities > 0) ? ($weekActivities->sum('percentage') / ($totalActivities * 100)) * 100 : 0,
        ];

        $mainRubro = 'Varios Rubros';
        if ($weekActivities->isNotEmpty()) {
            $rubros = $weekActivities->flatten()->map(function ($item) {
                return $item->activity->product->rubro->name ?? null;
            })->filter()->unique();

            if ($rubros->count() === 1) {
                $mainRubro = $rubros->first();
            } else if ($rubros->isEmpty()) {
                $mainRubro = 'Sin Rubro Asociado';
            }
        }

        $reportData = [
            'iniap_logo_path' => $iniap_logo_path,
            'ecuador_shield_path' => $ecuador_shield_path,
            'technician' => $technician,
            'technician_location' => $technician->location->name ?? 'Ubicación Desconocida',
            'program_rubro' => $mainRubro,
            'presentation_date' => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
            'week_range' => 'Del ' . $startDate->translatedFormat('d \d\e F \d\e Y') . ' al ' . $endDate->translatedFormat('d \d\e F \d\e Y'),
            'weekActivities' => $weekActivities,
            'days_of_week' => [
                'lunes', 'martes', 'miercoles', 'jueves', 'viernes',
            ],
            'start_date_obj' => $startDate,
        ];

        $pdf = Pdf::loadView('reports.weekly_plan', $reportData);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('Plan Semanal' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf');
    }

    public function getUserWeeklyPlans(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status' => 'nullable|string',
        ]);

        $query = WeekActivity::where('user_id', $user->id)
            ->with([
                'activity.product.rubro',
                'activity.users',
                'materials',
                'performanceIndicators',
                'logisticSupportUsers'
            ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        } else {
            $allVisibleStatuses = [
                'approved',
                'rated',
                'reassigned',
                'in progress',
                'pending',
                'completed',
                'partial',
                'not completed'
            ];
            $query->whereIn('status', $allVisibleStatuses);
        }

        $weeklyPlans = $query->orderBy('date', 'desc')->get();

        $formattedPlans = $weeklyPlans->map(function ($plan) {
            $productInitialCode = '';
            $activityInitialCode = '';
            $combinedCodePrefix = '';

            if ($plan->activity && $plan->activity->product && !empty($plan->activity->product->name)) {
                $productInitialCode = strtoupper(substr($plan->activity->product->name, 0, 2));
            }

            if ($plan->activity && !empty($plan->activity->description)) {
                $activityInitialCode = strtoupper(substr($plan->activity->description, 0, 2));
            }

            // Combinar los códigos si existen
            if (!empty($productInitialCode) && !empty($activityInitialCode)) {
                $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
            } elseif (!empty($productInitialCode)) {
                $combinedCodePrefix = $productInitialCode . ': ';
            } elseif (!empty($activityInitialCode)) {
                $combinedCodePrefix = $activityInitialCode . ': ';
            }


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
                'materials' => $plan->materials->map(function ($material) {
                    return [
                        'name' => $material->name,
                        'quantity' => $material->pivot->quantity,
                        'description' => $material->pivot->description,
                    ];
                }),
                'indicators' => $plan->performanceIndicators->pluck('name')->implode(' - '),
                'logistic_supports' => $plan->logisticSupportUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                })->toArray(),
            ];
        });

        return response()->json($formattedPlans);
    }

    public function getUserWeeklyPlansbyLocation(Request $request)
    {
        // 1. Validación de los parámetros
        $request->validate([
            'id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date_format:Y-m-d', // AÑADIDO
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date', // AÑADIDO
            'status' => 'nullable|string', // AÑADIDO
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // Usaremos el ID del request si se provee, sino, los usuarios de la misma ubicación
        $userIdsToQuery = [];
        if ($request->filled('id')) {
            $userIdsToQuery = [$request->input('id')];
        } else {
            $userIdsToQuery = User::where('location_id', $user->location_id)->pluck('id')->toArray();
        }

        if (empty($userIdsToQuery)) {
            return response()->json([]);
        }

        // 2. Construcción de la consulta base
        $query = WeekActivity::whereIn('user_id', $userIdsToQuery)
            ->with([
                'activity.product.rubro',
                'activity.users',
                'materials',
                'performanceIndicators',
                'logisticSupportUsers',
                'user'
            ]);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($request->filled('status')) {
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $statuses = explode(',', $request->input('status'));
                $query->whereIn('status', $statuses);
            } else {
                $allVisibleStatuses = [
                    'approved',
                    'rated',
                    'reassigned',
                    'in progress',
                    'pending',
                    'completed',
                    'partial',
                    'not completed'
                ];
                $query->whereIn('status', $allVisibleStatuses);
            }
        }

        $weeklyPlans = $query->orderBy('date', 'desc')->get();

        $formattedPlans = $weeklyPlans->map(function ($plan) {
            $productInitialCode = '';
            $activityInitialCode = '';
            $combinedCodePrefix = '';

            if ($plan->activity && $plan->activity->product && !empty($plan->activity->product->name)) {
                $productInitialCode = strtoupper(substr($plan->activity->product->name, 0, 2));
            }

            if ($plan->activity && !empty($plan->activity->description)) {
                $activityInitialCode = strtoupper(substr($plan->activity->description, 0, 2));
            }

            if (!empty($productInitialCode) && !empty($activityInitialCode)) {
                $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
            } elseif (!empty($productInitialCode)) {
                $combinedCodePrefix = $productInitialCode . ': ';
            } elseif (!empty($activityInitialCode)) {
                $combinedCodePrefix = $activityInitialCode . ': ';
            }

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
                'user_name' => $plan->user->name ?? 'N/A',
                'materials' => $plan->materials->map(function ($material) {
                    return [
                        'name' => $material->name,
                        'quantity' => $material->pivot->quantity,
                        'description' => $material->pivot->description,
                    ];
                }),
                'indicators' => $plan->performanceIndicators->pluck('name')->implode(' - '),
                'logistic_supports' => $plan->logisticSupportUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                    ];
                })->toArray(),
            ];
        });

        return response()->json($formattedPlans);
    }

    public function generateTeamPulseReport(Request $request)
    {
        Carbon::setLocale('es');
        $manager = $request->user();
        $manager->load('groups.members');

        // Por defecto, genera el reporte de la semana pasada
        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate = Carbon::now()->subWeek()->endOfWeek();

        // Obtener los IDs de los miembros del equipo
        $teamMemberIds = $manager->groups->flatMap(fn($group) => $group->members->pluck('id'))->unique();
        $teamMembers = User::whereIn('id', $teamMemberIds)->get();

        // Obtener los pulsos de esa semana para los miembros del equipo
        $pulses = WeeklyPulse::whereIn('user_id', $teamMemberIds)
            ->where('week_start_date', $startDate->toDateString())
            ->get()
            ->keyBy('user_id');

        // Combinar datos: todos los miembros con su pulso (o sin él)
        $teamPulseData = $teamMembers->map(function ($member) use ($pulses) {
            $pulse = $pulses->get($member->id);
            return [
                'name' => $member->name,
                'status' => $pulse->status ?? 'gray',
                'comment' => $pulse->comment ?? null,
            ];
        });

        // Calcular el resumen para el gráfico
        $counts = $teamPulseData->countBy('status');
        $total = $teamMembers->count() > 0 ? $teamMembers->count() : 1;
        $summary = [
            'total' => $teamMembers->count(),
            'counts' => [
                'green' => $counts->get('green', 0),
                'yellow' => $counts->get('yellow', 0),
                'red' => $counts->get('red', 0),
                'gray' => $counts->get('gray', 0),
            ],
            'percentages' => [
                'green' => round(($counts->get('green', 0) / $total) * 100),
                'yellow' => round(($counts->get('yellow', 0) / $total) * 100),
                'red' => round(($counts->get('red', 0) / $total) * 100),
                'gray' => round(($counts->get('gray', 0) / $total) * 100),
            ]
        ];

        // Preparar los datos para la vista
        $data = [
            'iniap_logo_path' => public_path('storage/images/iniap_logo.png'),
            'teamName' => $manager->groups->first()->name ?? 'Equipo', // O un nombre más genérico
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamPulseData' => $teamPulseData,
            'summary' => $summary,
        ];

        // Generar el PDF
        $pdf = Pdf::loadView('reports.team_pulse_report', $data);

        // Descargar el PDF
        return $pdf->download('informe-pulso-semanal-' . $startDate->format('Y-m-d') . '.pdf');
    }

    public function generateWeeklyMonitoringReport(Request $request)
    {
        Carbon::setLocale('es');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $iniap_logo_path = public_path('storage/images/iniap_logo.png');
        $ecuador_shield_path = public_path('storage/images/ecuador_shield.jpg');
        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $technician = User::with('location')->find($userId);
        if (!$technician) {
            return response()->json(['error' => 'Técnico no encontrado.'], 404);
        }

        $ratedStatuses = ['completed', 'partial', 'rated', 'not completed'];

        $weekActivities = WeekActivity::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', $ratedStatuses)
            ->with([
                'activity.product.rubro',
                'activity.users',
                'materials',
                'performanceIndicators',
                'logisticSupportUsers'
            ])
            ->orderBy('date')
            ->get();

        $weekActivities->each(function ($weekActivity) {
            $productInitialCode = '';
            $activityInitialCode = '';
            $combinedCodePrefix = '';

            if ($weekActivity->activity?->product?->name) {
                $productInitialCode = strtoupper(substr($weekActivity->activity->product->name, 0, 2));
            }
            if ($weekActivity->activity?->description) {
                $activityInitialCode = strtoupper(substr($weekActivity->activity->description, 0, 2));
            }
            if ($productInitialCode && $activityInitialCode) {
                $combinedCodePrefix = "{$productInitialCode}{$activityInitialCode}: ";
            } elseif ($productInitialCode) {
                $combinedCodePrefix = "{$productInitialCode}: ";
            } elseif ($activityInitialCode) {
                $combinedCodePrefix = "{$activityInitialCode}: ";
            }
            $weekActivity->formatted_description = $combinedCodePrefix . ($weekActivity->description ?? '');
        });

        $totalActivities = $weekActivities->count();
        $summary = [
            'completed' => $weekActivities->where('percentage', 100)->count(),
            'partial' => $weekActivities->where('percentage', '>', 0)->where('percentage', '<', 100)->count(),
            'not_done' => $weekActivities->where('percentage', 0)->count(),
            'overall_compliance' => ($totalActivities > 0) ? ($weekActivities->sum('percentage') / $totalActivities) : 0,
        ];

        $mainRubro = 'Varios Rubros';
        if ($weekActivities->isNotEmpty()) {
            $rubros = $weekActivities->map(function ($item) {
                return $item->activity->product->rubro->name ?? null;
            })->filter()->unique();

            if ($rubros->count() === 1) {
                $mainRubro = $rubros->first();
            } elseif ($rubros->isEmpty()) {
                $mainRubro = 'Sin Rubro Asociado';
            }
        }

        $reportData = [
            'iniap_logo_path' => $iniap_logo_path,
            'ecuador_shield_path' => $ecuador_shield_path,
            'technician' => $technician,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'weekActivities' => $weekActivities,
            'program_rubro' => $mainRubro,
        ];

        $pdf = Pdf::loadView('reports.weekly_monitoring_report', $reportData);
        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Informe_Monitoreo_' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf';
        return $pdf->download($fileName);
    }

    public function generateUserDeepDivePdf(Request $request, User $user)
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // --- OBTENEMOS LA NUEVA DATA ---
        // Obtenemos el registro de todas las actividades semanales finalizadas
        $allActivities = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->orderBy('date', 'desc')
            ->get();

        // Agrupamos las actividades por fecha para facilitar su uso en la vista Blade
        $groupedActivities = $allActivities->groupBy(function ($activity) {
            return Carbon::parse($activity->date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
        });

        // Los otros datos se obtienen igual
        $performanceStats = $this->getPerformanceStatsForUser($user, $startDate, $endDate);
        $weeklyLoadChart = $this->getWeeklyLoadForUser($user, $startDate, $endDate);
        $pulseHistory = $this->getPulseHistoryForUser($user, $startDate, $endDate);
        $collaborationStats = $this->getCollaborationStatsForUser($user, $startDate, $endDate);

        $data = [
            'reportDate' => Carbon::now()->locale('es')->isoFormat('LL'),
            'user' => $user,
            'startDate' => Carbon::parse($startDate)->locale('es')->isoFormat('LL'),
            'endDate' => Carbon::parse($endDate)->locale('es')->isoFormat('LL'),
            'performanceStats' => $performanceStats,
            'weeklyLoadChart' => $weeklyLoadChart,
            'pulseHistory' => $pulseHistory,
            'collaborationStats' => $collaborationStats,
            'groupedActivities' => $groupedActivities, // <-- Pasamos la nueva data a la vista
        ];

        $pdf = Pdf::loadView('reports.user_deep_dive_report', $data);
        return $pdf->download('informe-detallado-' . $user->name . '.pdf');
    }


    // ===================================================================
    // === MÉTODOS PRIVADOS AUXILIARES PARA RECOPILAR LOS DATOS ===
    // ===================================================================

    private function getPerformanceStatsForUser(User $user, $startDate, $endDate)
    {
        return WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
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
                    ->with(['weeklyActivities' => fn($q) => $q->where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])]);
            }])->get();
    }

    private function getWeeklyLoadForUser(User $user, $startDate, $endDate)
    {
        $weeks = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
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
            ->whereBetween('weekly_activities.date', [$startDate, $endDate])
            ->select('users.name')
            ->get()->countBy('name');

        return [
            'support_requested' => $activities->flatMap->logisticSupportUsers->countBy('name'),
            'support_given' => $supportGiven
        ];
    }

    public function getUserDeepDiveData(Request $request, User $user)
    {
        $validated = $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $allActivities = WeekActivity::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partial', 'not completed', 'rated'])
            ->orderBy('date', 'desc')
            ->get();

        $data = [
            'performanceStats' => $this->getPerformanceStatsForUser($user, $startDate, $endDate),
            'productBreakdown' => $this->getProductBreakdownForUser($user, $startDate, $endDate),
            'weeklyLoadChart' => $this->getWeeklyLoadForUser($user, $startDate, $endDate),
            'pulseHistory' => $this->getPulseHistoryForUser($user, $startDate, $endDate), // Lo modificaremos abajo
            'collaborationStats' => $this->getCollaborationStatsForUser($user, $startDate, $endDate),
            'allActivities' => $allActivities,
        ];

        return response()->json(['data' => $data]);
    }

    public function generateRubroDeepDivePdf(Request $request, Rubro $rubro)
    {
        $rubro->load([
            'groups' => function ($query) {
                $query->where('location_id', Auth::user()->location_id);
            },
            'products' => function ($query) {
                $query->where('location_id', Auth::user()->location_id)
                    ->with(['activities.weeklyActivities' => function ($q) {
                        $q->with('user:id,name')->orderBy('date', 'desc');
                    }]);
            }
        ]);

        $totalBudget = $rubro->products->sum('budget');

        $reportData = [
            'rubro' => [
                'name' => $rubro->name,
                'total_budget' => $totalBudget,
                'groups' => $rubro->groups->toArray(),
                'products' => $rubro->products->toArray(),
            ]
        ];

        $pdf = Pdf::loadView('reports.rubro_deep_dive_report', $reportData);
        return $pdf->download('informe_detallado_' . Str::slug($rubro->name) . '.pdf');
    }

    public function generateNationalExecutiveSummary(Request $request)
    {
        // --- FASE 1: OBTENCIÓN DE DATOS GLOBALES ---
        $locations = Location::all();
        $allProducts = Product::with(['activities.monthlyExecutionProgress'])->get();

        // --- MODIFICACIÓN CLAVE: Filtramos solo usuarios con el rol 'researcher' ---
        $allUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'researcher');
        })->get();

        $officialRubroId = Rubro::where('name', 'OFICIAL')->value('id');

        // Agrupamos para eficiencia
        $productsByLocation = $allProducts->groupBy('location_id');
        $usersByLocation = $allUsers->groupBy('location_id');

        // --- FASE 2: ANÁLISIS DETALLADO POR ESTACIÓN ---
        $detailedStationData = $locations->map(function ($location) use ($productsByLocation, $usersByLocation, $officialRubroId) {

            $stationId = $location->id;
            $locationProducts = $productsByLocation->get($stationId) ?? collect();
            // Ahora $locationUsers solo contiene investigadores
            $locationUsers = $usersByLocation->get($stationId) ?? collect();

            // (El resto de los cálculos no necesitan cambios, ya que ahora operan sobre los datos filtrados)
            $poaProducts = $locationProducts->where('rubro_id', '!=', $officialRubroId);
            $progress = $this->calculateTotalProgress($poaProducts);
            $totalBudget = $locationProducts->sum('budget');
            $recentDate = Carbon::now()->subDays(30);

            $activeProjectsCount = Product::where('location_id', $stationId)
                ->whereHas('activities.weeklyActivities', function ($query) use ($recentDate) {
                    $query->where('date', '>=', $recentDate);
                })
                ->count();

            $fourWeeksAgo = Carbon::now()->subWeeks(4)->startOfWeek();
            $recentProgress = WeekActivity::whereIn('user_id', $locationUsers->pluck('id'))
                ->where('date', '>=', $fourWeeksAgo)
                ->avg('percentage');

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

        // --- FASE 3: CONSOLIDACIÓN DE KPIs NACIONALES ---
        // (Los KPIs ahora reflejarán el conteo correcto de investigadores)
        $kpis = [
            'poa_progress' => round($detailedStationData->avg('poa_progress'), 2),
            'total_budget' => $detailedStationData->sum('total_budget'),
            'total_projects' => $detailedStationData->sum('project_count'),
            'total_researchers' => $detailedStationData->sum('researcher_count'),
            'active_stations' => $locations->count(),
        ];

        $dataForView = [
            'kpis' => $kpis,
            'stationData' => $detailedStationData->sortByDesc('poa_progress')->values(),
        ];

        $pdf = Pdf::loadView('reports.national_executive_summary', $dataForView);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('informe_situacion_nacional.pdf');
    }

    public function generateStationComparisonReport(Request $request)
    {
        // Usamos la lógica de NationalDashboardController para obtener los datos base
        $dashboardController = new NationalDashboardController();
        $performanceResponse = $dashboardController->getStationPerformance($request);
        $performanceData = collect($performanceResponse->getData()->data);

        // --- ENRIQUECEMOS LOS DATOS CON MÉTRICAS ADICIONALES ---
        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();

        $enrichedData = $performanceData->map(function ($stationData) use ($lastWeekStartDate) {
            $stationId = $stationData->location_id;

            // Calcular Presupuesto Total
            $stationData->total_budget = Product::where('location_id', $stationId)->sum('budget');

            // Calcular Pulso Promedio
            $memberIds = User::where('location_id', $stationId)->pluck('id');
            $pulses = WeeklyPulse::whereIn('user_id', $memberIds)
                ->where('week_start_date', $lastWeekStartDate->toDateString())
                ->get();

            if ($pulses->isEmpty() || $memberIds->isEmpty()) {
                $stationData->average_pulse_score = 0;
            } else {
                $pulseScoreMap = ['green' => 3, 'yellow' => 2, 'red' => 1];
                $totalScore = $pulses->reduce(fn($sum, $pulse) => $sum + ($pulseScoreMap[$pulse->status] ?? 0), 0);
                // Consideramos a los que no reportaron (gray) como 0 en el promedio
                $stationData->average_pulse_score = $totalScore / $memberIds->count();
            }

            return (array) $stationData;
        });

        // Ordenar por progreso para los rankings
        $sortedData = $enrichedData->sortByDesc('poa_progress')->values();

        // Identificar puntos clave para el resumen ejecutivo
        $dataForView = [
            'performanceData' => $sortedData,
            'topPerformer' => $sortedData->first(),
            'lowPerformer' => $sortedData->last(),
            'pulseAlert' => $sortedData->where('average_pulse_score', '>', 0)->sortBy('average_pulse_score')->first(),
        ];

        $pdf = Pdf::loadView('reports.station_comparison_report', $dataForView);
        return $pdf->download('reporte_comparativo_estaciones.pdf');
    }
}

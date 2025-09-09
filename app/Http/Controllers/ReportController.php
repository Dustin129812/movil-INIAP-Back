<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeekActivity;
use App\Models\WeeklyPulse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
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

        $weekActivities = WeekActivity::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
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

        // --- LÓGICA SIMPLIFICADA PARA GENERAR INICIALES/CÓDIGOS (MODIFICADO para unirse) ---
        $weekActivities->each(function ($dayActivities) {
            $dayActivities->each(function ($weekActivity) {
                $productInitialCode = ''; // Renombrado para claridad
                $activityInitialCode = ''; // Renombrado para claridad
                $combinedCodePrefix = ''; // Nuevo para el código combinado

                // Obtener las 2 primeras letras del nombre del producto
                if ($weekActivity->activity && $weekActivity->activity->product && !empty($weekActivity->activity->product->name)) {
                    $productInitialCode = strtoupper(substr($weekActivity->activity->product->name, 0, 2));
                }

                // Obtener las 2 primeras letras de la descripción de la actividad
                if ($weekActivity->activity && !empty($weekActivity->activity->description)) {
                    $activityInitialCode = strtoupper(substr($weekActivity->activity->description, 0, 2));
                }

                // Combinar los códigos si existen
                if (!empty($productInitialCode) && !empty($activityInitialCode)) {
                    $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
                } elseif (!empty($productInitialCode)) { // Si solo hay código de producto
                    $combinedCodePrefix = $productInitialCode . ': ';
                } elseif (!empty($activityInitialCode)) { // Si solo hay código de actividad
                    $combinedCodePrefix = $activityInitialCode . ': ';
                }


                // Añadir la descripción formateada al objeto WeekActivity para usarla en Blade
                $weekActivity->formatted_description = $combinedCodePrefix . ($weekActivity->description ?? '');
            });
        });
        // --- FIN DE LA LÓGICA DE INICIALES/CÓDIGOS ---

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

        if ($request->filled('status')) {
            if ($request->input('status') === 'all') {
                $query->whereIn('status', ['approved', 'rated', 'reassigned', 'in progress', 'pending']);
            } else {
                $statuses = explode(',', $request->input('status'));
                $query->whereIn('status', $statuses);
            }
        } else {
            $query->whereIn('status', ['approved', 'rated', 'reassigned', 'in progress', 'pending']);
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

        // 3. APLICACIÓN DE LOS FILTROS ADICIONALES (LÓGICA AÑADIDA)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($request->filled('status')) {
            if ($request->input('status') !== 'all') {
                $statuses = explode(',', $request->input('status'));
                $query->whereIn('status', $statuses);
            } else {
                // Si es 'all', traemos los estados relevantes por defecto
                $query->whereIn('status', ['approved', 'rated', 'reassigned', 'in progress', 'pending', 'rejected']);
            }
        } else {
            // Comportamiento por defecto si no se especifica estado
            $query->whereIn('status', ['approved', 'rated', 'reassigned', 'in progress', 'pending', 'rejected']);
        }

        // 4. Obtención y formateo de resultados
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
        // 1. Establecer el idioma para las fechas
        Carbon::setLocale('es');

        // 2. Validación de los parámetros de entrada
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        // 3. Rutas a los logos y obtención de datos básicos
        $iniap_logo_path = public_path('storage/images/iniap_logo.png');
        $ecuador_shield_path = public_path('storage/images/ecuador_shield.jpg');
        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        // 4. Obtención del técnico
        $technician = User::with('location')->find($userId);
        if (!$technician) {
            return response()->json(['error' => 'Técnico no encontrado.'], 404);
        }

        // 5. OBTENCIÓN DE ACTIVIDADES DE MONITOREO
        // ---- LA DIFERENCIA CLAVE: Se filtra por estado 'rated' ----
        $weekActivities = WeekActivity::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'rated') // Filtro esencial para el reporte de monitoreo
            ->with([
                'activity.product.rubro',
                'activity.users',
                'materials',
                'performanceIndicators',
                'logisticSupportUsers'
            ])
            ->orderBy('date')
            ->get(); // Se obtiene una colección simple, no agrupada

        // 6. Procesamiento para añadir descripción formateada (igual que en el plan)
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

        // 7. CÁLCULO DEL RESUMEN DE CUMPLIMIENTO (ESENCIAL PARA MONITOREO)
        $totalActivities = $weekActivities->count();
        $summary = [
            'completed' => $weekActivities->where('percentage', 100)->count(),
            'partial' => $weekActivities->where('percentage', '>', 0)->where('percentage', '<', 100)->count(),
            'not_done' => $weekActivities->where('percentage', 0)->count(),
            'overall_compliance' => ($totalActivities > 0) ? ($weekActivities->sum('percentage') / $totalActivities) : 0,
        ];

        // 8. Determinar el rubro principal del informe
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

        // 9. Preparación de todos los datos para la vista
        $reportData = [
            'iniap_logo_path' => $iniap_logo_path,
            'ecuador_shield_path' => $ecuador_shield_path,
            'technician' => $technician,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary, // Se envía el resumen de cumplimiento
            'weekActivities' => $weekActivities, // La colección PLANA y FILTRADA
            'program_rubro' => $mainRubro,
        ];

        // 10. Carga de la vista, configuración del PDF y generación
        $pdf = Pdf::loadView('reports.weekly_monitoring_report', $reportData);
        $pdf->setPaper('a4', 'landscape');

        $fileName = 'Informe_Monitoreo_' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf';
        return $pdf->download($fileName);
    }
}

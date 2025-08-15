<?php

namespace App\Http\Controllers;

use App\Exports\PoaReportExport;
use App\Models\User;
use App\Models\WeekActivity;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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
            'status' => 'nullable|in:pending,approved,rejected,in progress,completed',
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
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'approved');
        }

        $weeklyPlans = $query->orderBy('date', 'desc')->get();

        // --- LÓGICA SIMPLIFICADA PARA GENERAR INICIALES/CÓDIGOS PARA EL FRONTEND (MODIFICADO para unirse) ---
        $formattedPlans = $weeklyPlans->map(function ($plan) {
            $productInitialCode = ''; // Renombrado para claridad
            $activityInitialCode = ''; // Renombrado para claridad
            $combinedCodePrefix = ''; // Nuevo para el código combinado

            // Obtener las 2 primeras letras del nombre del producto
            if ($plan->activity && $plan->activity->product && !empty($plan->activity->product->name)) {
                $productInitialCode = strtoupper(substr($plan->activity->product->name, 0, 2));
            }

            // Obtener las 2 primeras letras de la descripción de la actividad
            if ($plan->activity && !empty($plan->activity->description)) {
                $activityInitialCode = strtoupper(substr($plan->activity->description, 0, 2));
            }

            // Combinar los códigos si existen
            if (!empty($productInitialCode) && !empty($activityInitialCode)) {
                $combinedCodePrefix = $productInitialCode . $activityInitialCode . ': ';
            } elseif (!empty($productInitialCode)) { // Si solo hay código de producto
                $combinedCodePrefix = $productInitialCode . ': ';
            } elseif (!empty($activityInitialCode)) { // Si solo hay código de actividad
                $combinedCodePrefix = $activityInitialCode . ': ';
            }

            // --- FIN DE LA LÓGICA DE INICIALES/CÓDIGOS ---

            return [
                'id' => $plan->id,
                'date' => Carbon::parse($plan->date)->isoFormat('dddd, D [de] MMMM [de]YYYY'),
                // Usa la descripción formateada aquí para el frontend
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
        $request->validate([
            'id' => 'nullable',
        ]);
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        // Obtener IDs de usuarios con la misma location_id
        $locationUserIds = User::where('location_id', $user->location_id)->pluck('id');

        if ($locationUserIds->isEmpty()) {
            return response()->json([]); // No hay usuarios con la misma ubicación
        }

        $weeklyPlans = WeekActivity::whereIn('user_id', $locationUserIds)->when($request->filled('id'), function ($query) use ($request) {
            $query->where('user_id', $request->id);
        })->where('status', 'approved') // puedes quitar esto si quieres traer todos
        ->with([
            'activity.product.rubro',
            'activity.users',
            'materials',
            'performanceIndicators',
            'logisticSupportUsers',
            'user'
        ])
            ->orderBy('date', 'desc')
            ->get();

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
}

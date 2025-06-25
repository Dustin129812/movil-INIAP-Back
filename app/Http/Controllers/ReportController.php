<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeekActivity;
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

        $iniap_logo_path = asset('storage/app/public/images/iniap_logo.png');
        $ecuador_shield_path = asset('storage/app/public/images/ecuador_shield.jpg');

        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $technician = User::with('location')->find($userId); // <-- Carga la relación 'location' del usuario
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
                'logisticSupports'
            ])
            ->orderBy('date')
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        // Intentar determinar el rubro principal de las actividades de esa semana
        $mainRubro = 'Varios Rubros'; // Valor por defecto
        if ($weekActivities->isNotEmpty()) {
            $rubros = $weekActivities->flatten()->map(function($item) {
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
            'technician_location' => $technician->location->name ?? 'Ubicación Desconocida', // <-- Pasa la ubicación
            'program_rubro' => $mainRubro, // <-- Pasa el rubro principal
            'presentation_date' => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
            'week_range' => 'Del ' . $startDate->translatedFormat('d \d\e F \d\e Y') . ' al ' . $endDate->translatedFormat('d \d\e F \d\e Y'), // <-- CAMBIO AQUÍ
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
        $user = Auth::user(); // Obtener el usuario autenticado
        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d', // Hacemos opcional para ver todas o filtrar
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'status' => 'nullable|in:pending,approved,rejected,in progress,completed', // Filtrar por status
        ]);

        $query = WeekActivity::where('user_id', $user->id) // Filtrar por el usuario logueado
        ->with([
            'activity.product.rubro',
            'activity.users',
            'materials',
            'performanceIndicators',
            'logisticSupports'
        ]);

        // Aplicar filtros de fecha si se proporcionan
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        // Aplicar filtro de estado
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            // Por defecto, solo mostrar las 'approved' si no se especifica otro estado
            $query->where('status', 'approved'); // Filtrar por 'approved' por defecto si no se pide otro estado
        }


        $weeklyPlans = $query->orderBy('date', 'desc')->get();

        // Transformar los datos para el frontend (opcional, pero útil para consolidar)
        $formattedPlans = $weeklyPlans->map(function($plan) {
            return [
                'id' => $plan->id,
                'date' => Carbon::parse($plan->date)->isoFormat('dddd, D [de] MMMM [de] YYYY'), // Formato legible en español
                'description' => $plan->description ?? ($plan->activity->description ?? 'N/A'), // Usa la descripción de WeekActivity, o Activity
                'estimated_hours' => $plan->estimated_hours,
                'work_location' => $plan->work_location,
                'observations' => $plan->observations,
                'status' => $plan->status,
                'activity_base_id' => $plan->activity->id ?? null,
                'product_name' => $plan->activity->product->name ?? 'N/A',
                'rubro_name' => $plan->activity->product->rubro->name ?? 'N/A',
                'responsables' => $plan->activity->users->pluck('name')->implode(', ') ?? 'N/A',
                'materials' => $plan->materials->map(function($material) {
                    return [
                        'name' => $material->name,
                        'quantity' => $material->pivot->quantity,
                        'description' => $material->pivot->description,
                    ];
                }),
                'indicators' => $plan->performanceIndicators->pluck('name')->implode(' - '), // Concatenar indicadores
                'logistic_supports' => $plan->logisticSupports->pluck('name')->implode(', '),
            ];
        });

        return response()->json($formattedPlans);
    }
}

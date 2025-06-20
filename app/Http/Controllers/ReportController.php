<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WeekActivity;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function generateWeeklyPlanReport(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $userId = $request->input('user_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        // Obtener el técnico
        $technician = User::find($userId);
        if (!$technician) {
            return response()->json(['error' => 'Técnico no encontrado.'], 404);
        }

        // Obtener las WeekActivities para el técnico y el rango de fechas, cargando todas las relaciones necesarias
        $weekActivities = WeekActivity::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with([
                'activity.product.rubro',
                'activity.users',
                'materials.pivot',
                'performanceIndicators',
                'logisticSupports'
            ])
            ->orderBy('date')
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d'); // Agrupa por día para la tabla
            });

        // Preparar los datos para la vista
        $reportData = [
            'technician' => $technician,
            'presentation_date' => Carbon::now()->format('d-m-Y'),
            'week_range' => 'Del ' . $startDate->format('d \de F \de Y') . ' al ' . $endDate->format('d \de F \de Y'),
            'weekActivities' => $weekActivities,
            'days_of_week' => [
                'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sábado', 'domingo'
            ],
            'start_date_obj' => $startDate, // <-- PASAR EL OBJETO CARBON DE START_DATE
        ];

        $pdf = Pdf::loadView('reports.weekly_plan', $reportData);

        return $pdf->download('plan_semanal_' . str_replace(' ', '_', $technician->name) . '_' . $startDate->format('Ymd') . '.pdf');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ChartController extends Controller
{
    public function getChartData(Request $request)
    {
        try {
            $metric = $request->query('metric', 'rubros');
            $filters = $request->query('filters', []);
            $user = $request->user();

            $query = Product::query()->with(['rubro', 'location', 'activities.monthlyProgress', 'activities.weekActivities']);

            if ($user) {
                $query->where('products.user_id', $user->id);
            }

            if (!empty($filters['fecha_inicio'])) {
                $query->whereHas('activities', function ($q) use ($filters) {
                    $q->where('start_date', '>=', $filters['fecha_inicio']);
                });
            }

            if (!empty($filters['fecha_fin'])) {
                $query->whereHas('activities', function ($q) use ($filters) {
                    $q->where('end_date', '<=', $filters['fecha_fin']);
                });
            }

            if (!empty($filters['location_id'])) {
                $query->where('location_id', $filters['location_id']);
            }

            $data = [];

            if ($metric === 'rubros') {
                $data = $query->selectRaw('rubros.name as label, COALESCE(SUM(products.budget), 0) as value')
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->groupBy('rubros.name')
                    ->get()
                    ->map(fn($item) => ['label' => $item->label, 'value' => (float) $item->value]);
            }

            elseif ($metric === 'locations') {
                $data = $query->selectRaw('locations.name as label, COALESCE(SUM(products.budget), 0) as value')
                    ->join('locations', 'products.location_id', '=', 'locations.id')
                    ->groupBy('locations.name')
                    ->get()
                    ->map(fn($item) => ['label' => $item->label, 'value' => (float) $item->value]);
            }

            elseif ($metric === 'monthly_progress') {
                $data = $query->selectRaw('DATE_FORMAT(activity_monthly_progress.month, "%Y-%m") as label, COALESCE(AVG(activity_monthly_progress.percentage), 0) as value')
                    ->join('activities', 'products.id', '=', 'activities.product_id')
                    ->join('activity_monthly_progress', 'activities.id', '=', 'activity_monthly_progress.activity_id')
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get()
                    ->map(fn($item) => ['label' => $item->label, 'value' => (float) $item->value]);
            }

            elseif ($metric === 'poa_vs_extra_poa') {
                $data = $query->selectRaw("
                    CASE
                        WHEN TRIM(rubros.name) ILIKE 'Actividades Extra POA' THEN 'Actividades Extra POA'
                        ELSE 'POA'
                    END as label,
                    COALESCE(SUM(weekly_activities.estimated_hours), 0) as value
                ")
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->leftJoin('activities', 'products.id', '=', 'activities.product_id')
                    ->leftJoin('weekly_activities', 'activities.id', '=', 'weekly_activities.activity_id')
                    ->groupBy('label')
                    ->get()
                    ->map(fn($item) => ['label' => $item->label, 'value' => (float) $item->value]);
            }

            elseif ($metric === 'time_by_rubro') {
                $data = $query->selectRaw("
                    CASE
                        WHEN TRIM(rubros.name) ILIKE 'Actividades Extra POA' THEN 'Actividades Extra POA'
                        ELSE 'POA'
                    END as label,
                    COALESCE(SUM(weekly_activities.estimated_hours), 0) as value
                ")
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->leftJoin('activities', 'products.id', '=', 'activities.product_id')
                    ->leftJoin('weekly_activities', 'activities.id', '=', 'weekly_activities.activity_id')
                    ->groupBy('label')
                    ->get()
                    ->map(fn($item) => ['label' => $item->label, 'value' => (float) $item->value]);
            }

            elseif ($metric === 'poa_progress_by_station') {
                $data = $query->selectRaw('
                    locations.name as station,
                    COALESCE(AVG(activity_monthly_progress.percentage), 0) as projection,
                    COALESCE(AVG(weekly_activities.percentage), 0) as execution
                ')
                    ->join('locations', 'products.location_id', '=', 'locations.id')
                    ->leftJoin('activities', 'products.id', '=', 'activities.product_id')
                    ->leftJoin('activity_monthly_progress', 'activities.id', '=', 'activity_monthly_progress.activity_id')
                    ->leftJoin('weekly_activities', 'activities.id', '=', 'weekly_activities.activity_id')
                    ->groupBy('locations.name')
                    ->get()
                    ->map(fn($item) => [
                        'station' => $item->station,
                        'projection' => (float) $item->projection,
                        'execution' => (float) $item->execution,
                    ]);
            }
            elseif ($metric === 'rubro_progress') {
                $data = $query->selectRaw('
                   rubros.name as label,
                   COALESCE(AVG(activity_monthly_progress.percentage), 0) as value
                ')
    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
    ->leftJoin('activities', 'products.id', '=', 'activities.product_id')
    ->leftJoin('activity_monthly_progress', 'activities.id', '=', 'activity_monthly_progress.activity_id')
    ->groupBy('rubros.name')
    ->get()
    ->map(fn($item) => [
        'label' => $item->label,
        'value' => round((float) $item->value, 2)
    ]);
}


            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Datos para gráficos obtenidos correctamente',
                    'code' => 200,
                ],
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener los datos: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }
}

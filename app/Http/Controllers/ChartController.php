<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Importar la clase Log

class ChartController extends Controller
{
    public function getChartData(Request $request)
    {
        try {
            $metric = $request->query('metric', 'rubros');
            Log::info('Métrica recibida: ' . $metric); // Log para verificar la métrica
            $filters = $request->query('filters', []);
            $user = $request->user();

            // Verificar si el usuario está autenticado para métricas específicas de investigador
            $researcherMetrics = [
                'researcher_products_progress',
                'researcher_activities_progress',
                'researcher_activity_weighted_progress',
                'researcher_product_weighted_progress',
                'researcher_monthly_activity_weighted_progress', // Nueva métrica
                'researcher_monthly_product_weighted_progress'   // Nueva métrica
            ];
            if (in_array($metric, $researcherMetrics) && !$user) {
                throw new \Exception("Se requiere autenticación para obtener el avance de productos y actividades del investigador.");
            }

            // Consulta base para métricas relacionadas con el Producto
            // Solo se aplica el filtro de usuario para las métricas que no son de investigador
            if ($user && !in_array($metric, $researcherMetrics)) {
                $query = Product::query()->with(['rubro', 'location', 'activities.monthlyProgress', 'activities.weekActivities'])
                                 ->where('products.user_id', $user->id);
            } else {
                 $query = Product::query()->with(['rubro', 'location', 'activities.monthlyProgress', 'activities.weekActivities']);
            }


            // Filtros adicionales (aplicados a consultas basadas en producto)
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

            // Lógica por métrica
            $data = [];

            if ($metric === 'rubros') {
                $data = $query->selectRaw('rubros.name as label, COALESCE(SUM(products.budget), 0) as value')
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->groupBy('rubros.name')
                    ->get()
                    ->map(fn($item) => [
                        'label' => $item->label,
                        'value' => (float) $item->value,
                    ]);
            } elseif ($metric === 'locations') {
                $data = $query->selectRaw('locations.name as label, COALESCE(SUM(products.budget), 0) as value')
                    ->join('locations', 'products.location_id', '=', 'locations.id')
                    ->groupBy('locations.name')
                    ->get()
                    ->map(fn($item) => [
                        'label' => $item->label,
                        'value' => (float) $item->value,
                    ]);
            } elseif ($metric === 'monthly_progress') {
                $data = $query->selectRaw("to_char(activity_monthly_progress.month, 'YYYY-MM') as label, COALESCE(AVG(activity_monthly_progress.percentage), 0) as value")
                    ->join('activities', 'products.id', '=', 'activities.product_id')
                    ->join('activity_monthly_progress', 'activities.id', '=', 'activity_monthly_progress.activity_id')
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get()
                    ->map(fn($item) => [
                        'label' => $item->label,
                        'value' => (float) $item->value,
                    ]);
            } elseif ($metric === 'poa_vs_extra_poa') {
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
                    ->map(fn($item) => [
                        'label' => $item->label,
                        'value' => (float) $item->value,
                    ]);
            } elseif ($metric === 'time_by_rubro') {
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
                    ->map(fn($item) => [
                        'label' => $item->label,
                        'value' => (float) $item->value,
                    ]);
            } elseif ($metric === 'poa_progress_by_station') {
                $data = $query->selectRaw("
                    locations.name as station,
                    COALESCE(AVG(activity_monthly_progress.percentage), 0) as projection,
                    COALESCE(AVG(weekly_activities.percentage), 0) as execution
                ")
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
            } elseif ($metric === 'researcher_products_progress') {
                Log::info('Entrando en la lógica de researcher_products_progress (Avance General de Productos)');

                $productIds = Activity::whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })->pluck('product_id')->unique();

                Log::info('Product IDs encontrados para el investigador: ' . $productIds->toJson());

                if ($productIds->isEmpty()) {
                    Log::info('No se encontraron productos asociados al investigador. Devolviendo 0%.');
                    $data = collect([[
                        'label' => 'Sin Productos Asignados',
                        'value' => 0.0,
                    ]]);
                } else {
                    Log::info('Productos encontrados. Calculando promedio.');
                    $overallAvg = DB::table('activity_monthly_progress')
                        ->join('activities', 'activity_monthly_progress.activity_id', '=', 'activities.id')
                        ->whereIn('activities.product_id', $productIds)
                        ->avg('activity_monthly_progress.percentage');

                    Log::info('Promedio general de productos calculado: ' . ($overallAvg ?? 'NULL'));

                    $data = collect([[
                        'label' => 'Avance General de Productos',
                        'value' => (float) ($overallAvg ?? 0),
                    ]]);
                }
            } elseif ($metric === 'researcher_activities_progress') {
                Log::info('Entrando en la lógica de researcher_activities_progress');
                $activityIds = Activity::whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })->pluck('id');

                Log::info('Activity IDs encontrados para el investigador: ' . $activityIds->toJson());

                if ($activityIds->isEmpty()) {
                    Log::info('No se encontraron actividades asignadas al investigador. Devolviendo 0%.');
                    $data = collect([[
                        'label' => 'Sin Actividades Asignadas',
                        'value' => 0.0,
                    ]]);
                } else {
                    Log::info('Actividades encontradas. Calculando promedio.');
                    $data = DB::table('activities')
                        ->selectRaw('activities.description as label, COALESCE(AVG(activity_monthly_progress.percentage), 0) as value')
                        ->leftJoin('activity_monthly_progress', 'activities.id', '=', 'activity_monthly_progress.activity_id')
                        ->whereIn('activities.id', $activityIds)
                        ->groupBy('activities.description')
                        ->get()
                        ->map(fn($item) => [
                            'label' => $item->label,
                            'value' => (float) $item->value,
                        ]);
                }
            } elseif ($metric === 'researcher_activity_weighted_progress') { // Avance Ponderado de Actividades Global
                Log::info('Entrando en la lógica de researcher_activity_weighted_progress (Avance Ponderado de Actividades GLOBAL)');

                $weightedResults = null;
                $weightedResults = DB::table('activity_monthly_progress as amp')
                    ->join('activities as a', 'amp.activity_id', '=', 'a.id')
                    ->join('activity_user as au', 'a.id', '=', 'au.activity_id')
                    ->where('au.user_id', $user->id)
                    ->selectRaw('SUM(amp.percentage * a.ponderacion) / NULLIF(SUM(a.ponderacion), 0) as actual_weighted_avg')
                    ->first();

                $actualValue = ($weightedResults && isset($weightedResults->actual_weighted_avg)) ? (float) $weightedResults->actual_weighted_avg : 0.0;
                $plannedValue = 100.0;

                Log::info('Avance Ponderado de Actividades Global (Actual): ' . $actualValue . ' | Planificado: ' . $plannedValue);

                $data = collect([[
                    'label' => 'Avance Ponderado de Actividades',
                    'actual_value' => $actualValue,
                    'planned_value' => $plannedValue,
                ]]);
            } elseif ($metric === 'researcher_product_weighted_progress') { // Avance Ponderado de Productos Global
                Log::info('Entrando en la lógica de researcher_product_weighted_progress (Avance Ponderado de Productos GLOBAL)');

                $productIdsForResearcher = Activity::whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })->pluck('product_id')->unique();

                Log::info('Product IDs para ponderación de productos: ' . $productIdsForResearcher->toJson());

                $actualValue = 0.0;
                $plannedValue = 100.0;
                $weightedResults = null;

                if ($productIdsForResearcher->isEmpty()) {
                     Log::info('No se encontraron productos para ponderación. Devolviendo 0%.');
                } else {
                    $weightedResults = DB::table('activity_monthly_progress as amp')
                        ->join('activities as a', 'amp.activity_id', '=', 'a.id')
                        ->join('products as p', 'a.product_id', '=', 'p.id')
                        ->whereIn('p.id', $productIdsForResearcher)
                        ->selectRaw('SUM(amp.percentage * p.ponderacion) / NULLIF(SUM(p.ponderacion), 0) as actual_weighted_avg')
                        ->first();

                    $actualValue = ($weightedResults && isset($weightedResults->actual_weighted_avg)) ? (float) $weightedResults->actual_weighted_avg : 0.0;
                    Log::info('Avance Ponderado de Productos Global (Actual): ' . $actualValue . ' | Planificado: ' . $plannedValue);
                }

                $data = collect([[
                    'label' => 'Avance Ponderado de Productos',
                    'actual_value' => $actualValue,
                    'planned_value' => $plannedValue,
                ]]);
            } elseif ($metric === 'researcher_monthly_activity_weighted_progress') { // NUEVA MÉTRICA: Avance Ponderado Mensual de Actividades
                Log::info('Entrando en la lógica de researcher_monthly_activity_weighted_progress (Avance Ponderado Mensual de Actividades)');

                $activityIds = Activity::whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })->pluck('id');

                if ($activityIds->isEmpty()) {
                    Log::info('No se encontraron actividades para el avance mensual ponderado. Devolviendo 0%.');
                    $data = []; // Retorna un array vacío para que el frontend lo maneje
                } else {
                    $results = DB::table('activity_monthly_progress as amp')
                        ->selectRaw("
                            to_char(amp.month, 'YYYY-MM') as month_label,
                            SUM(amp.percentage * a.ponderacion) / NULLIF(SUM(a.ponderacion), 0) as actual_weighted_avg_monthly
                        ")
                        ->join('activities as a', 'amp.activity_id', '=', 'a.id')
                        ->whereIn('amp.activity_id', $activityIds)
                        ->groupBy('month_label')
                        ->orderBy('month_label')
                        ->get();

                    $data = $results->map(fn($item) => [
                        'label' => $item->month_label,
                        'actual_value' => (float) ($item->actual_weighted_avg_monthly ?? 0.0),
                        'planned_value' => 100.0, // Planificado es 100% para cada mes
                    ])->toArray();

                    Log::info('Avance Ponderado Mensual de Actividades calculado: ' . json_encode($data));
                }
            } elseif ($metric === 'researcher_monthly_product_weighted_progress') { // NUEVA MÉTRICA: Avance Ponderado Mensual de Productos
                Log::info('Entrando en la lógica de researcher_monthly_product_weighted_progress (Avance Ponderado Mensual de Productos)');

                $productIdsForResearcher = Activity::whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })->pluck('product_id')->unique();

                if ($productIdsForResearcher->isEmpty()) {
                    Log::info('No se encontraron productos para el avance mensual ponderado. Devolviendo 0%.');
                    $data = []; // Retorna un array vacío para que el frontend lo maneje
                } else {
                    $results = DB::table('activity_monthly_progress as amp')
                        ->selectRaw("
                            to_char(amp.month, 'YYYY-MM') as month_label,
                            SUM(amp.percentage * p.ponderacion) / NULLIF(SUM(p.ponderacion), 0) as actual_weighted_avg_monthly
                        ")
                        ->join('activities as a', 'amp.activity_id', '=', 'a.id')
                        ->join('products as p', 'a.product_id', '=', 'p.id')
                        ->whereIn('p.id', $productIdsForResearcher)
                        ->groupBy('month_label')
                        ->orderBy('month_label')
                        ->get();

                    $data = $results->map(fn($item) => [
                        'label' => $item->month_label,
                        'actual_value' => (float) ($item->actual_weighted_avg_monthly ?? 0.0),
                        'planned_value' => 100.0, // Planificado es 100% para cada mes
                    ])->toArray();

                    Log::info('Avance Ponderado Mensual de Productos calculado: ' . json_encode($data));
                }
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
            Log::error('Error en getChartData: ' . $e->getMessage());
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

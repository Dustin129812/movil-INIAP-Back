<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rubro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartController extends Controller
{
    public function getChartData(Request $request)
    {
        try {
            // Parámetros de la solicitud
            $metric = $request->query('metric', 'rubros');
            $filters = $request->query('filters', []);
            $user = $request->user();

            // Inicializar la consulta base
            $query = Product::query()->with(['rubro', 'location', 'activities.monthlyProgress', 'activities.weekActivities']);

            // Filtrar por usuario si está autenticado
            if ($user) {
                $query->where('products.user_id', $user->id); // Especificar products.user_id
            }

            // Aplicar filtros adicionales
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

            // Lógica según la métrica seleccionada
            $data = [];
            if ($metric === 'rubros') {
                $data = $query->selectRaw('rubros.name as label, COALESCE(SUM(products.budget), 0) as value')
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->groupBy('rubros.name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'value' => (float) $item->value,
                        ];
                    });
            } elseif ($metric === 'locations') {
                $data = $query->selectRaw('locations.name as label, COALESCE(SUM(products.budget), 0) as value')
                    ->join('locations', 'products.location_id', '=', 'locations.id')
                    ->groupBy('locations.name')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'value' => (float) $item->value,
                        ];
                    });
            } elseif ($metric === 'monthly_progress') {
                $data = $query->selectRaw('DATE_FORMAT(activity_monthly_progress.month, "%Y-%m") as label, COALESCE(AVG(activity_monthly_progress.percentage), 0) as value')
                    ->join('activities', 'products.id', '=', 'activities.product_id')
                    ->join('activity_monthly_progress', 'activities.id', '=', 'activity_monthly_progress.activity_id')
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'value' => (float) $item->value,
                        ];
                    });
            } elseif ($metric === 'poa_vs_extra_poa') {
                $data = $query->selectRaw('
                    CASE
                        WHEN TRIM(rubros.name) ILIKE \'Actividades Extra POA\' THEN \'Actividades Extra POA\'
                        ELSE \'POA\'
                    END as label,
                    COALESCE(SUM(weekly_activities.estimated_hours), 0) as value
                ')
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->leftJoin('activities', 'products.id', '=', 'activities.product_id')
                    ->leftJoin('weekly_activities', 'activities.id', '=', 'weekly_activities.activity_id')
                    ->groupBy('label')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'value' => (float) $item->value,
                        ];
                    });
            } elseif ($metric === 'time_by_rubro') {
                $data = $query->selectRaw('
                    CASE
                        WHEN TRIM(rubros.name) ILIKE \'Actividades Extra POA\' THEN \'Actividades Extra POA\'
                        ELSE \'POA\'
                    END as label,
                    COALESCE(SUM(weekly_activities.estimated_hours), 0) as value
                ')
                    ->join('rubros', 'products.rubro_id', '=', 'rubros.id')
                    ->leftJoin('activities', 'products.id', '=', 'activities.product_id')
                    ->leftJoin('weekly_activities', 'activities.id', '=', 'weekly_activities.activity_id')
                    ->groupBy('label')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'label' => $item->label,
                            'value' => (float) $item->value,
                        ];
                    });
            } elseif ($metric === 'poa_progress_by_station') {
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
                    ->map(function ($item) {
                        return [
                            'station' => $item->station,
                            'projection' => (float) $item->projection,
                            'execution' => (float) $item->execution,
                        ];
                    });
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

public function adminMaterials(Request $request)
{
    $user = auth()->user();
    $type = $request->input('tipo');
    $productId = $request->input('product_id');
    $rubroId = $request->input('rubro_id');


    if (!$user || !$user->location_id) {
        return response()->json([
            'msg' => [
                'summary' => 'Error de autenticación',
                'detail' => 'El usuario no tiene una ubicación asignada',
                'code' => 403,
            ],
        ], 403);
    }

    switch ($type) {
        case 'top':
            $materiales = DB::table('material_week_activity')
                ->selectRaw('materials.name as material, SUM(material_week_activity.quantity) as total_used')
                ->join('materials', 'material_week_activity.material_id', '=', 'materials.id')
                ->join('weekly_activities', 'material_week_activity.week_activity_id', '=', 'weekly_activities.id')
                ->join('activities', 'weekly_activities.activity_id', '=', 'activities.id')
                ->join('products', 'activities.product_id', '=', 'products.id')
                ->where('products.location_id', $user->location_id)
                ->where('weekly_activities.status', 'approved')
                ->groupBy('materials.name')
                ->orderByDesc('total_used')
                ->limit(10)
                ->get();

            return response()->json([
                'msg' => ['summary' => 'Top materiales', 'detail' => 'Consulta exitosa', 'code' => 200],
                'data' => $materiales,
            ]);

        case 'producto':
            if (!$productId) {
                return response()->json([
                    'msg' => ['summary' => 'Falta product_id', 'detail' => '', 'code' => 400],
                ], 400);
            }

            $product = Product::findOrFail($productId);

            $materiales = DB::table('material_week_activity')
                ->selectRaw('materials.name as material, SUM(material_week_activity.quantity) as total_used')
                ->join('materials', 'material_week_activity.material_id', '=', 'materials.id')
                ->join('weekly_activities', 'material_week_activity.week_activity_id', '=', 'weekly_activities.id')
                ->join('activities', 'weekly_activities.activity_id', '=', 'activities.id')
                ->where('activities.product_id', $product->id)
                ->where('weekly_activities.status', 'approved')
                ->groupBy('materials.name')
                ->orderByDesc('total_used')
                ->get();

            return response()->json([
                'msg' => ['summary' => 'Materiales por producto', 'detail' => 'Consulta exitosa', 'code' => 200],
                'data' => ['product' => $product->name, 'materials' => $materiales],
            ]);

        case 'rubro':
            if (!$rubroId) {
                return response()->json([
                    'msg' => ['summary' => 'Falta rubro_id', 'detail' => '', 'code' => 400],
                ], 400);
            }

            $rubro = Rubro::findOrFail($rubroId);

            $productos = Product::where('rubro_id', $rubroId)
                ->where('location_id', $user->location_id)
                ->select('id', 'name')
                ->get()
                ->map(function ($producto) {

            $materiales = DB::table('material_week_activity')
                ->selectRaw('materials.name as material, SUM(material_week_activity.quantity) as total_used')
                ->join('materials', 'material_week_activity.material_id', '=', 'materials.id')
                ->join('weekly_activities', 'material_week_activity.week_activity_id', '=', 'weekly_activities.id')
                ->join('activities', 'weekly_activities.activity_id', '=', 'activities.id')
                ->where('activities.product_id', $producto->id)
                ->where('weekly_activities.status', 'approved')
                ->groupBy('materials.name')
                ->orderByDesc('total_used')
                ->get()
                        ->map(fn($item) => [
                            'label' => $item->material,
                            'value' => (float) $item->total_used,
                        ]);

                    return [
                        'product' => $producto->name,
                        'materials' => $materiales,
                    ];
                });

            return response()->json([
                'msg' => ['summary' => 'Materiales por rubro y producto', 'detail' => 'Consulta exitosa', 'code' => 200],
                'data' => [
                    'rubro' => $rubro->name,
                    'products' => $productos,

                ],
            ]);

        default:
            return response()->json([
                'msg' => ['summary' => 'Tipo inválido', 'detail' => 'Debe ser top, producto o rubro', 'code' => 400],
            ], 400);
    }
}


}

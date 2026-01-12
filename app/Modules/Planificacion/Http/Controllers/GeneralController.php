<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Http\Resources\ActivityResource;
use App\Modules\Planificacion\Models\Activity;
use App\Modules\Planificacion\Models\Ethnic_Group;
use App\Modules\Planificacion\Models\Location;
use App\Modules\Planificacion\Models\LogisticSupport;
use App\Modules\Planificacion\Models\Nationality;
use App\Modules\Planificacion\Models\Position;
use App\Modules\Planificacion\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;

class GeneralController extends Controller
{
    public function getLocations()
    {
        $locations = Location::get();
        return $locations;
    }

    public function getNationality()
    {
        $nationality = Nationality::get();
        return $nationality;
    }

    public function getEthnics()
    {
        $ethnic = Ethnic_Group::get();
        return $ethnic;
    }

    public function getPositions()
    {
        $position = Position::get();
        return $position;
    }

    public function getLogistic(): JsonResponse
    {
        $logistics = LogisticSupport::all();
        return response()->json($logistics);
    }

    public function getProducts(Request $request) // Se añade Request para poder leer parámetros
    {
        try {
            if ($request->has('user_id') && $request->user_id) {
                $userId = $request->input('user_id');
            } else {
                $userId = Auth::id();
            }
            if (!$userId) {
                return response()->json(['message' => 'No se pudo determinar el usuario.'], 401);
            }

            $products = Product::with(['activity.users'])
                ->whereUserRelated($userId)
                ->get();

            return response()->json(['data' => $products]);

        } catch (\Exception $e) {
            Log::error('Error al obtener productos: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'message' => 'Error al obtener los productos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

//admin-Station
    public function getProductsByLocation()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            if (!$user->location_id) {
                return response()->json(['message' => 'El usuario no tiene una ubicación asignada.'], 400);
            }

            $products = DB::table('products')
                ->where('location_id', $user->location_id)
                ->get();

            $productos_con_materiales = [];

            foreach ($products as $product) {
                $materiales = DB::table('material_week_activity')
                    ->join('materials', 'material_week_activity.material_id', '=', 'materials.id')
                    ->join('weekly_activities', 'material_week_activity.week_activity_id', '=', 'weekly_activities.id')
                    ->join('activities', 'weekly_activities.activity_id', '=', 'activities.id')
                    ->where('activities.product_id', $product->id)
                    ->whereIn('weekly_activities.status', ['approved', 'completed'])
                    ->selectRaw('materials.name as material, SUM(material_week_activity.quantity) as total_used')
                    ->groupBy('materials.name')
                    ->get();

                if ($materiales->isNotEmpty()) {
                    $productos_con_materiales[] = [
                        'id' => $product->id,
                        'producto' => $product->name,
                        'materials' => $materiales,
                    ];
                }
            }

            return response()->json(['data' => $productos_con_materiales]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos con materiales: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Error al obtener los productos con materiales.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRubrosByLocation()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            if (!$user->location_id) {
                return response()->json(['message' => 'El usuario no tiene una ubicación asignada.'], 400);
            }

            $rubros = DB::table('rubros')
                ->join('products', 'rubros.id', '=', 'products.rubro_id')
                ->where('products.location_id', $user->location_id)
                ->select('rubros.id', 'rubros.name')
                ->distinct()
                ->get();

            $result = [];

            foreach ($rubros as $rubro) {
                $products = DB::table('products')
                    ->where('location_id', $user->location_id)
                    ->where('rubro_id', $rubro->id)
                    ->get();

                $productos_con_materiales = [];

                foreach ($products as $product) {
                    $materiales = DB::table('material_week_activity')
                        ->selectRaw('materials.name as material, SUM(material_week_activity.quantity) as total_used')
                        ->join('materials', 'material_week_activity.material_id', '=', 'materials.id')
                        ->join('weekly_activities', 'material_week_activity.week_activity_id', '=', 'weekly_activities.id')
                        ->join('activities', 'weekly_activities.activity_id', '=', 'activities.id')
                        ->where('activities.product_id', $product->id)
                        ->whereIn('weekly_activities.status', ['approved', 'completed'])
                        ->groupBy('materials.name')
                        ->orderByDesc('total_used')
                        ->get();

                    if ($materiales->isNotEmpty()) {
                        $productos_con_materiales[] = [
                            'id' => $product->id,
                            'producto' => $product->name,
                            'materials' => $materiales,
                        ];
                    }
                }

                if (!empty($productos_con_materiales)) {
                    $result[] = [
                        'id' => $rubro->id,
                        'rubro' => $rubro->name,
                        'productos' => $productos_con_materiales,
                    ];
                }
            }

            return response()->json([
                'msg' => [
                    'summary' => 'Materiales por productos agrupados por rubro',
                    'detail' => 'Consulta exitosa',
                    'code' => 200,
                ],
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener rubros por ubicación: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Error al obtener los rubros por ubicación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActivitiesByProduct(int $productId): JsonResponse
{
    try {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado.'
            ], 404);
        }

        //Traer todas las actividades del producto, sin importar el usuario
        $activities = Activity::where('product_id', $productId)
            ->with(['users', 'indicators'])
            ->get();

        if ($activities->isEmpty()) {
            return response()->json([
                'message' => 'Este producto no tiene actividades registradas.'
            ], 404);
        }

        return response()->json(['data' => ActivityResource::collection($activities)]);
    } catch (\Exception $e) {
        Log::error('Error al obtener actividades: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'product_id' => $productId,
        ]);
        return response()->json([
            'message' => 'Error al obtener las actividades.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function addLogisticSupport(Request $request): JsonResponse
    {
        try {
            $supportName = $request->input('name');

            if (empty($supportName)) {
                return response()->json(['error' => 'El nombre del Soporte no puede estar vacío.'], 400);
            }

            $existingSupport = LogisticSupport::whereRaw('LOWER(name) = ?', [strtolower($supportName)])->first();

            if ($existingSupport) {
                return response()->json(['error' => 'El Soporte logístico "' . $supportName . '" ya existe.'], 409);
            }

            $support = new LogisticSupport();
            $support->name = $supportName;
            $support->save();

            return response()->json($support, 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el Soporte Logístico', 'details' => $e->getMessage()], 500);
        }
    }
}

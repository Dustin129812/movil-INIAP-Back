<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Ethnic_Group;
use App\Models\Location;
use App\Models\LogisticSupport;
use App\Models\Nationality;
use App\Models\Performance_Indicator;
use App\Models\Position;
use App\Models\Product;
use App\Models\Rubro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;

class GeneralController extends Controller
{
    public function getLocations(){
        $locations = Location::get();
        return $locations;
    }

    public function getNationality(){
        $nationality = Nationality::get();
        return $nationality;
    }

    public function getEthnics(){
        $ethnic = Ethnic_Group::get();
        return $ethnic;
    }

    public function getPositions(){
        $position = Position::get();
        return $position;
    }
    public function getRubros(){
        $rubro = Rubro::get();
        return $rubro;
    }

    public function getIndicators(): JsonResponse
    {
        $indicators = Performance_Indicator::all(); // Usa all() para obtener todos
        return response()->json($indicators); // Devuelve la respuesta como JSON
    }

    public function getLogistic(): JsonResponse
    {
        $logistics = LogisticSupport::all(); // Usa all() para obtener todos
        return response()->json($logistics); // Devuelve la respuesta como JSON
    }

    public function getProducts()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            $products = Product::with(['activity.users'])
                ->whereUserRelated($userId)
                ->get();

            return response()->json(['data' => $products]); // Devuelve { data: [...] }
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

   /**
     * Obtiene actividades para un producto específico, filtradas por el usuario autenticado
     * que es responsable de esas actividades.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivitiesByProduct(int $productId): JsonResponse
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            // Verificar si el producto existe (opcional, pero buena práctica)
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'message' => 'Producto no encontrado.'
                ], 404);
            }

            // Obtener actividades relacionadas a este producto Y donde el usuario autenticado es un responsable
            $activities = Activity::where('product_id', $productId)
                ->whereHas('users', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->with(['users', 'indicators']) // Cargar relaciones necesarias para ActivityResource
                ->get();

            if ($activities->isEmpty()) {
                return response()->json([
                    'message' => 'No hay actividades relacionadas a este producto para el usuario autenticado.'
                ], 404);
            }

            // Usar ActivityResource para formatear la colección de actividades
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

    public function addRubro(Request $request): JsonResponse
    {
        try {
            $rubroName = $request->input('name');

            // 1. Validar que el nombre no sea nulo o vacío
            if (empty($rubroName)) {
                return response()->json(['error' => 'El nombre del rubro no puede estar vacío.'], 400);
            }

            // 2. Buscar si ya existe un rubro con ese nombre (ignorando mayúsculas/minúsculas para una mejor UX)
            $existingRubro = Rubro::whereRaw('LOWER(name) = ?', [strtolower($rubroName)])->first();

            if ($existingRubro) {
                // Si ya existe, devuelve un error 409 Conflict
                return response()->json(['error' => 'El rubro "' . $rubroName . '" ya existe.'], 409);
            }

            // Si no existe, procede a crearlo
            $rubro = new Rubro();
            $rubro->name = $rubroName;
            $rubro->save();

            return response()->json($rubro, 201);

        } catch (\Exception $e) {
            // Un error general de servidor
            return response()->json(['error' => 'Error al crear el rubro', 'details' => $e->getMessage()], 500);
        }
    }

    public function addIndicator(Request $request): JsonResponse
    {
        try {
            $indicatorName = $request->input('name');

            //Validar que el nombre no sea nulo o vacío
            if (empty($indicatorName)) {
                return response()->json(['error' => 'El nombre del indicador no puede estar vacío.'], 400);
            }

            //Buscar si ya existe un indicador con ese nombre (ignorando mayúsculas/minúsculas)
            $existingIndicator = Rubro::whereRaw('LOWER(name) = ?', [strtolower($indicatorName)])->first();

            if ($existingIndicator) {
                //Si ya existe, devuelve un error 409 Conflict
                return response()->json(['error' => 'El indicador "' . $indicatorName . '" ya existe.'], 409);
            }

            //Si no existe, procede a crearlo
            $indicator = new Performance_Indicator();
            $indicator->name = $indicatorName;
            $indicator->save();

            return response()->json($indicator, 201);

        } catch (\Exception $e) {
            // Un error general de servidor
            return response()->json(['error' => 'Error al crear el indicador', 'details' => $e->getMessage()], 500);
        }
    }


    public function addLogisticSupport(Request $request): JsonResponse
    {
        try {
            $supportName = $request->input('name');

            //Validar que el nombre no sea nulo o vacío
            if (empty($supportName)) {
                return response()->json(['error' => 'El nombre del Soporte no puede estar vacío.'], 400);
            }

            //Buscar si ya existe un indicador con ese nombre (ignorando mayúsculas/minúsculas)
            $existingSupport = LogisticSupport::whereRaw('LOWER(name) = ?', [strtolower($supportName)])->first();

            if ($existingSupport) {
                //Si ya existe, devuelve un error 409 Conflict
                return response()->json(['error' => 'El Soporte logístico "' . $supportName . '" ya existe.'], 409);
            }

            //Si no existe, procede a crearlo
            $support = new LogisticSupport();
            $support->name = $supportName;
            $support->save();

            return response()->json($support, 201);

        } catch (\Exception $e) {
            // Un error general de servidor
            return response()->json(['error' => 'Error al crear el Soporte Logístico', 'details' => $e->getMessage()], 500);
        }
    }
}

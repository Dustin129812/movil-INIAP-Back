<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
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

        $products = Product::where('location_id', $user->location_id)->get();

        return response()->json(['data' => $products]);
    } catch (\Exception $e) {
        Log::error('Error al obtener productos por ubicación: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'message' => 'Error al obtener los productos por ubicación.',
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

        $rubros = Rubro::whereHas('product', function ($query) use ($user) {
            $query->where('location_id', $user->location_id);
        })->get();

        return response()->json(['data' => $rubros]);
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

    public function getActivitiesByProduct($productId)
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            $product = Product::with(['activity.users', 'activity.indicators'])
                ->whereUserRelated($userId)
                ->find($productId);

            if (!$product) {
                return response()->json([
                    'message' => 'Producto no encontrado o no autorizado.'
                ], 404);
            }

            if ($product->activity->isEmpty()) {
                return response()->json([
                    'message' => 'No hay actividades para este producto.'
                ], 404);
            }

            return response()->json(['data' => ActivityResource::collection($product->activity)]);
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
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Models\Ethnic_Group;
use App\Models\Location;
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

            Log::info('Productos cargados para usuario:', [
                'user_id' => $userId,
                'products_count' => $products->count(),
            ]);

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

    public function getActivitiesByProduct($productId)
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            $product = Product::with(['activity.users', 'activity.indicator'])
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

            Log::info('Actividades cargadas para producto:', [
                'product_id' => $productId,
                'user_id' => $userId,
                'activities_count' => $product->activity->count(),
                'users_count' => $product->activity->pluck('users')->flatten()->count(),
                'indicators_loaded' => $product->activity->pluck('indicator')->filter()->count(),
            ]);

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

    public function addRubro(Request $request): JsonResponse // Especifica el tipo de retorno
    {
        try {
            $rubro = new Rubro();
            $rubro->name = $request->input('name');
            $rubro->save();

            return response()->json($rubro, 201); // Devuelve el nuevo rubro y el código 201 (Created)
            // O si prefieres un mensaje:
            // return response()->json(['message' => 'Rubro creado exitosamente', 'rubro' => $rubro], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el rubro', 'details' => $e->getMessage()], 500); // Manejo de errores
        }
    }

    public function addIndicator(Request $request): JsonResponse
    {
        try {
            $indicator = new Performance_Indicator();
            $indicator->name = $request->input('name');
            $indicator->save();

            return response()->json($indicator, 201); // Devuelve el nuevo indicador y código 201
            // O:
            // return response()->json(['message' => 'Indicador creado exitosamente', 'indicator' => $indicator], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el indicador', 'details' => $e->getMessage()], 500); // Manejo de errores
        }
    }
}

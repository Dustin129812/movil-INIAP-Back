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
        $userId = Auth::id();
        $products = Product::whereUserRelated($userId)->get();

        return response()->json($products);
    }

    public function getActivitiesByProduct($productId)
    {
        $userId = Auth::id();

        $product = Product::with(['activity.indicator']) // 👈 importante: cargar el indicador
        ->whereUserRelated($userId)
            ->find($productId);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o no autorizado.'], 404);
        }

        if ($product->activity->isEmpty()) {
            return response()->json(['message' => 'No hay actividades para este producto.'], 404);
        }

        // 👇 Retornar usando un Resource que incluya el indicador_name
        return ActivityResource::collection($product->activity);
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

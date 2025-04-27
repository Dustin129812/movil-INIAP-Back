<?php

namespace App\Http\Controllers;

use App\Models\Ethnic_Group;
use App\Models\Location;
use App\Models\Nationality;
use App\Models\Performance_Indicator;
use App\Models\Position;
use App\Models\Product;
use App\Models\Rubro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function getIndicators(){
        $indicators = Performance_Indicator::get();
        return $indicators;
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

        $product = Product::with(['activity' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }])
            ->whereUserRelated($userId)
            ->find($productId);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado o no autorizado.'], 404);
        }

        if ($product->activity->isEmpty()) {
            return response()->json(['message' => 'No hay actividades para este producto.'], 404);
        }

        return response()->json($product->activity);
    }

    public function addRubro(Request $request){
        $rubro = new Rubro();
        $rubro->name = $request->input('name');

        $rubro->save();
    }

    public function addIndicator(Request $request){
        $indicator = new Performance_Indicator();
        $indicator->name = $request->input('name');

        $indicator->save();
    }
}

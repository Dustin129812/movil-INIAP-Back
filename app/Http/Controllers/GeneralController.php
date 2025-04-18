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

    public function getProducts(){
        $products = Product::get();
        return $products;
    }

    public function getActivitiesByProduct($productId)
    {
        $product = Product::with('activity')->find($productId);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        if ($product->activity->isEmpty()) {
            return response()->json(['message' => 'No hay actividades para este producto.'], 404);
        }

        return response()->json($product->activity);
    }





}

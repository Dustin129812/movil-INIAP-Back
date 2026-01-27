<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\ProdVarietyType;

class VarietyTypeController extends Controller
{
    public function index()
    {
        return response()->json(ProdVarietyType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        $type = ProdVarietyType::create(['name' => $request->name]);
        return response()->json($type, 201);
    }
}

<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\ProdLot;

class LotController extends Controller
{
    public function index()
    {
        return response()->json(Lot::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'surface'  => 'required|numeric',
            'location' => 'required|string'
        ]);

        $lot = ProdLot::create($request->all());
        return response()->json($lot, 201);
    }
}

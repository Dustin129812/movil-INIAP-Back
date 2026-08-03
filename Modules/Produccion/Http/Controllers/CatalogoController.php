<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Produccion\Entities\Bodega;
use Modules\Produccion\Entities\Insumo;
use Modules\Produccion\Entities\Maquinaria;
use Modules\Produccion\Entities\UnidadMedida;
use Modules\Produccion\Traits\ApiResponse;

// Importamos los FormRequests
use Modules\Produccion\Http\Requests\Catalogo\StoreBodegaRequest;
use Modules\Produccion\Http\Requests\Catalogo\StoreUnidadRequest;
use Modules\Produccion\Http\Requests\Catalogo\StoreInsumoRequest;
use Modules\Produccion\Http\Requests\Catalogo\StoreMaquinariaRequest;

class CatalogoController extends Controller
{
    use ApiResponse;

    // --- BODEGAS ---
    public function storeBodega(StoreBodegaRequest $request)
    {
        return $this->createdResponse(Bodega::create($request->validated()));
    }

    // --- UNIDADES DE MEDIDA ---
    public function storeUnidad(StoreUnidadRequest $request)
    {
        return $this->createdResponse(UnidadMedida::create($request->validated()));
    }

    // --- INSUMOS ---
    public function storeInsumo(StoreInsumoRequest $request)
    {
        return $this->createdResponse(Insumo::create($request->validated()));
    }

    // --- MAQUINARIA ---
    public function storeMaquinaria(StoreMaquinariaRequest $request)
    {
        return $this->createdResponse(Maquinaria::create($request->validated()));
    }

    // --- INDEX GLOBAL ---
    public function index()
    {
        return $this->successResponse([
            'bodegas'    => Bodega::all(['id', 'nombre']),
            'unidades'   => UnidadMedida::all(['id', 'nombre', 'abreviatura']),
            'insumos'    => Insumo::with('unidadMedida')->get(),
            'maquinaria' => Maquinaria::all(['id', 'nombre', 'costo_hora', 'placa_serie'])
        ]);
    }
}

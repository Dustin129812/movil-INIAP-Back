<?php

namespace App\Modules\Planificacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Http\Requests\Requests\StoreRubroRequest;
use App\Modules\Planificacion\Http\Requests\Requests\UpdateRubroRequest;
use App\Modules\Planificacion\Models\Rubro;
use Illuminate\Http\JsonResponse;

class RubroController extends Controller
{
    public function index()
    {
        $rubro = Rubro::get();
        return $rubro;
    }

    public function store(StoreRubroRequest $request): JsonResponse
    {
        $rubro = Rubro::create($request->validated());
        return response()->json($rubro, 201);
    }

    public function show(Rubro $rubro): JsonResponse
    {
        return response()->json($rubro);
    }

    public function update(UpdateRubroRequest $request, Rubro $rubro): JsonResponse
    {
        $rubro->update($request->validated());
        return response()->json($rubro);
    }

    public function destroy(Rubro $rubro): JsonResponse
    {
        // SoftDeletes se maneja automáticamente por el modelo
        $rubro->delete();
        return response()->json(null, 204);
    }
}

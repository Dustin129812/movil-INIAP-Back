<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Http\Requests\StoreRubroRequest;
use Modules\Investigacion\Http\Requests\UpdateRubroRequest;

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

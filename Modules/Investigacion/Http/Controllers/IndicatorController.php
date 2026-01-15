<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Investigacion\Entities\Performance_Indicator;
use Modules\Investigacion\Http\Requests\StoreIndicatorRequest;
use Modules\Investigacion\Http\Requests\UpdateIndicatorRequest;

class IndicatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Performance_Indicator::query();

        $query->when($request->input('search'), function ($q, $search) {
            return $q->where('name', 'like', "%{$search}%");
        });

        return response()->json($query->latest()->paginate(15));
    }

    public function store(StoreIndicatorRequest $request): JsonResponse
    {
        $indicator = Performance_Indicator::create($request->validated());
        return response()->json($indicator, 201);
    }

    public function show(Performance_Indicator $indicator): JsonResponse
    {
        return response()->json($indicator);
    }

    public function update(UpdateIndicatorRequest $request, Performance_Indicator $indicator): JsonResponse
    {
        $indicator->update($request->validated());
        return response()->json($indicator);
    }

    public function destroy(Performance_Indicator $indicator): JsonResponse
    {
        $indicator->delete();
        return response()->json(null, 204);
    }
}

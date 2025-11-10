<?php

namespace App\Modules\Planificacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Http\Resources\StationNeedResource;
use App\Modules\Planificacion\Models\StationNeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StationNeedController extends Controller
{
    public function index()
    {
        $needs = StationNeed::with('user', 'location', 'expenseType')->latest()->get();

        return StationNeedResource::collection($needs);
    }

    /**
     * Muestra un reporte de necesidad específico.
     */
    public function show(StationNeed $stationNeed)
    {
        $stationNeed->load('user.position', 'location.province', 'location.canton');

        return new StationNeedResource($stationNeed);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'needs' => 'required|array|min:1',
            'needs.*.expense_type_id' => 'required|integer|exists:expense_types,id',
            'needs.*.description' => 'required|string',
            'needs.*.estimated_amount' => 'required|numeric|min:0',
            'needs.*.priority' => ['required', 'string', Rule::in(['Alta', 'Media', 'Baja'])],
            'needs.*.expected_impact' => 'required|string',
            'needs.*.impact_type' => ['required', 'string', Rule::in(['Directo', 'Indirecto'])],
            'needs.*.problem_to_solve' => 'required|string',
            'needs.*.investment_risk' => 'required|string',
            'needs.*.administrative_time_months' => 'required|integer|min:0',
            'needs.*.execution_time_months' => 'required|integer|min:0',
            'needs.*.has_supporting_documents' => 'required|boolean',
            'needs.*.requires_technical_studies' => 'required|boolean',
            'needs.*.has_technical_studies' => 'nullable|boolean|required_if:needs.*.requires_technical_studies,true',
        ]);

        $user = Auth::user();
        if (!$user->location_id) {
            return response()->json(['message' => 'Tu usuario no tiene una estación asignada.'], 400);
        }

        DB::transaction(function () use ($validated, $user) {
            foreach ($validated['needs'] as $needData) {
                $needData['user_id'] = $user->id;
                $needData['location_id'] = $user->location_id;
                $needData['fill_date'] = now()->toDateString();

                StationNeed::create($needData);
            }
        });

        return response()->json(['message' => 'Reportes de necesidad creados exitosamente.'], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ReusableActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReusableActivityController extends Controller
{
    // En app/Http/Controllers/ReusableActivityController.php
    public function index()
    {
        $user = Auth::user();
        $reusableActivities = ReusableActivity::with([
            'activity.product',
            'materials',
            'performanceIndicators',
            'logisticSupportUsers'
        ])
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $reusableActivities]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'activityId' => 'required|exists:activities,id',
            'description' => 'required|string',
            'work_location' => 'nullable|string',
            'observations' => 'nullable|string',
            'materials' => 'nullable|array',
            'indicators' => 'nullable|array',
            'logisticSupports' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $reusable = ReusableActivity::create([
                'user_id' => Auth::id(),
                'activity_id' => $request->activityId,
                'name' => $request->name,
                'description' => $request->description,
                'work_location' => $request->work_location,
                'observations' => $request->observations,
            ]);

            if ($request->has('materials')) {
                $materialSyncData = [];
                foreach ($request->materials as $material) {
                    $materialSyncData[$material['id']] = [
                        'quantity' => $material['pivot']['quantity'] ?? null,
                        'description' => $material['pivot']['description'] ?? null
                    ];
                }
                $reusable->materials()->sync($materialSyncData);
            }

            if ($request->has('indicators')) {
                $reusable->performanceIndicators()->sync($request->indicators);
            }

            if ($request->has('logisticSupports')) {
                $reusable->logisticSupportUsers()->sync($request->logisticSupports);
            }

            DB::commit();
            return response()->json($reusable->load('materials', 'performanceIndicators', 'logisticSupportUsers'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar la actividad reutilizable: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $reusableActivity = ReusableActivity::findOrFail($id);

        if ($reusableActivity->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $reusableActivity->delete();

        return response()->json(null, 204);
    }
}

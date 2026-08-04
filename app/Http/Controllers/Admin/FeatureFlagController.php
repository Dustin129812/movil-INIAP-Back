<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function index()
    {
        return response()->json(FeatureFlag::all());
    }

    public function update(Request $request, FeatureFlag $featureFlag)
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $featureFlag->update(['is_active' => $validated['is_active']]);
        return response()->json($featureFlag);
    }
}

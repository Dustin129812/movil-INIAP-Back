<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PulseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:green,yellow,red',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $lastWeekStartDate = Carbon::now()->subWeek()->startOfWeek();

        $user->weeklyPulses()->updateOrCreate(
            ['week_start_date' => $lastWeekStartDate],
            [
                'status' => $validated['status'],
                'comment' => $validated['comment'],
            ]
        );

        return response()->json(['message' => 'Pulso semanal guardado con éxito.'], 200);
    }
}

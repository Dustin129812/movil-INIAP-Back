<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Investigacion\Entities\NoveltyActivity;

class NoveltyController extends Controller
{
    public function storeBatch(Request $request)
    {

        Log::info('Recibiendo lote de novedades:', $request->all());
        DB::beginTransaction();
        try {
            $noveltiesData = $request->input('novelties');
            $userId = Auth::id();

            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

            $dayOffsets = [
                'lunes' => 0, 'martes' => 1, 'miercoles' => 2,
                'jueves' => 3, 'viernes' => 4, 'sábado' => 5, 'domingo' => 6,
            ];

            foreach ($noveltiesData as $data) {
                $activityDate = $startOfWeek->copy()->addDays($dayOffsets[$data['day']] ?? 0);

                $novelty = NoveltyActivity::create([
                    'user_id' => $userId,
                    'activity_id' => $data['activityId'] ?: null,
                    'description' => $data['description'],
                    'observations' => $data['observations'] ?? null,
                    'date' => $activityDate,
                    'work_location' => $data['work_location'] ?? null,
                    'percentage' => 100,
                    'status' => 'completed',
                ]);

                $materials = array_filter($data['materials'] ?? []);
                if (!empty($materials)) {
                    $novelty->materials()->sync($materials);
                }

                $indicators = array_filter($data['indicators'] ?? []);
                if (!empty($indicators)) {
                    $novelty->indicators()->sync($indicators);
                }

                $logisticSupports = array_filter($data['logisticSupports'] ?? []);
                if (!empty($logisticSupports)) {
                    $novelty->logisticSupport()->sync($logisticSupports);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Novedades registradas correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en batch de novedades: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getForCurrentWeek(Request $request)
    {
        try {
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            $novelties = NoveltyActivity::where('user_id', Auth::id())
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->with(['activity.product', 'materials', 'indicators', 'logisticSupport'])
                ->orderBy('date', 'desc')
                ->get();

            $novelties->each(function ($item) {
                $item->type = 'novelty'; // Marcador explícito
            });

            return response()->json($novelties);

        } catch (\Exception $e) {
            Log::error("Error al obtener novedades de la semana: " . $e->getMessage());
            return response()->json(['error' => 'Error al obtener las novedades.'], 500);
        }
    }

}

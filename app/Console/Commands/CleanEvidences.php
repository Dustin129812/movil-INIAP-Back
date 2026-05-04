<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Investigacion\Entities\WeekActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanEvidences extends Command
{
    protected $signature = 'simpagi:clean-evidences';

    protected $description = 'Elimina físicamente los verificables de planificación mayores a 8 días para liberar espacio en el servidor.';

    public function handle()
    {
        $thresholdDate = Carbon::now()->subDays(8);

        $activities = WeekActivity::whereNotNull('evidence_path')
            ->where('updated_at', '<', $thresholdDate)
            ->get();

        $deletedCount = 0;

        foreach ($activities as $activity) {
            if (Storage::disk('local')->exists($activity->evidence_path)) {
                Storage::disk('local')->delete($activity->evidence_path);
            }

            $activity->evidence_path = null;
            $activity->save();

            $deletedCount++;
        }

        $message = "Limpieza de verificables completada. Se eliminaron {$deletedCount} archivos del servidor.";
        $this->info($message);
        Log::channel('daily')->info($message);
    }
}

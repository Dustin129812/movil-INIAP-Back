<?php

namespace Modules\Administracion\Console;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Administracion\Entities\Dispatch;
use Modules\Administracion\Entities\Vehicle;

class ManageFleetAvailability extends Command
{
    protected $name = 'administracion:manage-fleet';
    protected $description = 'Automatiza el bloqueo y liberación de vehículos basados en sus fechas y horas de salida y retorno.';

    public function handle()
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $tripsStarting = Dispatch::where('status', 'pending')
                ->whereHas('mobilization', function($q) use ($now) {
                    $q->where('departure_time', '<=', $now);
                })->get();

            foreach($tripsStarting as $trip) {
                $trip->update(['status' => 'processing']);
                if ($trip->vehicle_id) {
                    Vehicle::where('id', $trip->vehicle_id)->update(['is_available' => false]);
                }
            }

            $tripsEnding = Dispatch::where('status', 'processing')
                ->whereHas('mobilization', function($q) use ($now) {
                    $q->where('return_time', '<=', $now);
                })->get();

            foreach($tripsEnding as $trip) {
                $trip->update(['status' => 'dispatched']);
                if ($trip->vehicle_id) {
                    Vehicle::where('id', $trip->vehicle_id)->update(['is_available' => true]);
                }
            }
        });

        $this->info('Flota vehicular sincronizada con el reloj atómico exitosamente.');
    }
}

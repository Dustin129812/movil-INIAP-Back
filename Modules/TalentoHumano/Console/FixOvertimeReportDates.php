<?php

namespace Modules\TalentoHumano\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\TalentoHumano\Entities\ThOvertimeEntry;
use Modules\TalentoHumano\Entities\ThOvertimeReport;
use Modules\TalentoHumano\Entities\ThEmployeeConfig;

class FixOvertimeReportDates extends Command
{
    /**
     * El nombre y la firma del comando.
     * --dry-run : Ejecuta el script sin guardar cambios (modo prueba)
     */
    protected $signature = 'th:fix-report-dates {--dry-run : Si se activa, no guardará cambios en la BD}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Reasigna las actividades al reporte del mes correcto basándose en la fecha de la actividad.';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("MODO DE PRUEBA (DRY-RUN): No se realizarán cambios en la base de datos.");
        } else {
            $this->alert("MODO PRODUCCIÓN: Se modificarán los registros.");
            // Pequeña pausa de seguridad
            sleep(3);
        }

        $this->info("Iniciando escaneo de actividades...");

        // Obtenemos todas las entradas junto con su reporte actual
        // Procesamos en bloques (chunks) para no saturar la memoria
        $countMoved = 0;
        $countErrors = 0;

        ThOvertimeEntry::with('report')->chunk(100, function ($entries) use ($isDryRun, &$countMoved, &$countErrors) {

            foreach ($entries as $entry) {
                if (!$entry->report) {
                    $this->error("La entrada ID {$entry->id} no tiene reporte asociado. Saltando...");
                    continue;
                }

                $entryDate = Carbon::parse($entry->date);

                // Mes y Año de la actividad
                $activityMonth = $entryDate->month;
                $activityYear = $entryDate->year;

                // Mes y Año del reporte actual
                $reportMonth = $entry->report->month;
                $reportYear = $entry->report->year;

                // Verificamos si hay discrepancia
                if ($activityMonth != $reportMonth || $activityYear != $reportYear) {

                    $this->line("Detectada discrepancia en Entrada ID: {$entry->id} (Fecha: {$entry->date}). Reporte actual: {$reportMonth}/{$reportYear}. Debería ser: {$activityMonth}/{$activityYear}");

                    if ($isDryRun) {
                        $countMoved++;
                        continue;
                    }

                    try {
                        DB::transaction(function () use ($entry, $activityMonth, $activityYear) {
                            $driverId = $entry->report->driver_id;

                            // 1. Buscar si ya existe el reporte correcto
                            $targetReport = ThOvertimeReport::where('driver_id', $driverId)
                                ->where('month', $activityMonth)
                                ->where('year', $activityYear)
                                ->first();

                            // 2. Si no existe, crearlo
                            if (!$targetReport) {
                                // Buscar config para el RMU
                                $config = ThEmployeeConfig::where('user_id', $driverId)->first();

                                // Si no hay config, usamos valores por defecto o del reporte anterior (fallback)
                                $rmu = $config ? $config->rmu : $entry->report->rmu_at_submission;
                                $hourValue = $rmu / 240;

                                $targetReport = ThOvertimeReport::create([
                                    'driver_id' => $driverId,
                                    'month' => $activityMonth,
                                    'year' => $activityYear,
                                    'status' => 'borrador', // Por defecto nace en borrador
                                    'rmu_at_submission' => $rmu,
                                    'hour_value' => $hourValue,
                                    'submitted_at' => null
                                ]);

                                $this->info(" -> Creado nuevo reporte ID {$targetReport->id} para {$activityMonth}/{$activityYear}");
                            } else {
                                // ADVERTENCIA: Si el reporte destino ya fue enviado/aprobado
                                if ($targetReport->status !== 'borrador') {
                                    $this->warn(" -> ¡OJO! El reporte destino ID {$targetReport->id} está en estado '{$targetReport->status}'. Se moverá la entrada de todas formas.");
                                }
                            }

                            // 3. Mover la entrada
                            $entry->overtime_report_id = $targetReport->id;
                            $entry->save();
                        });

                        $this->info(" -> CORREGIDO: Entrada movida exitosamente.");
                        $countMoved++;

                    } catch (\Exception $e) {
                        $this->error(" -> Error al mover entrada {$entry->id}: " . $e->getMessage());
                        $countErrors++;
                    }
                }
            }
        });

        $this->newLine();
        $this->info("Proceso finalizado.");
        $this->info("Total entradas corregidas: {$countMoved}");
        $this->info("Total errores: {$countErrors}");
    }
}

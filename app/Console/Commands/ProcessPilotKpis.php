<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Investigacion\Entities\WeekActivity;

class ProcessPilotKpis extends Command
{
    /**
     * La firma del comando de consola.
     */
    protected $signature = 'pilot:process-kpis';

    /**
     * La descripción del comando.
     */
    protected $description = 'Procesa los datos del piloto filtrando por ubicación y genera un informe de KPIs.';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        $this->info("🚀 Iniciando el procesamiento de KPIs (versión final)...");

        // --- CONFIGURACIÓN IMPORTANTE ---
        $startDate = Carbon::parse('2025-08-04')->startOfDay(); // Cambia a tu fecha real de inicio
        $endDate = $startDate->copy()->addMonth()->endOfDay(); // Analizando 1 mes

        $this->line("Analizando datos desde {$startDate->toDateString()} hasta {$endDate->toDateString()}");

        // ----- LÓGICA SIMPLIFICADA Y CORREGIDA PARA FILTRAR POR UBICACIÓN -----
        $this->line("Filtrando usuarios para la ubicación del piloto (ID=4)...");
        $pilotLocationId = 4;

        // La tabla 'users' tiene una relación directa con 'locations', así que la consulta es muy simple.
        $pilotUsers = User::where('location_id', $pilotLocationId)->get();
        $pilotUserIds = $pilotUsers->pluck('id'); // Obtenemos los IDs para usarlos en otras consultas

        if ($pilotUsers->isEmpty()) {
            $this->error("Advertencia: No se encontraron usuarios con location_id = 4. Revisa los datos en tu tabla 'users'.");
            return self::FAILURE;
        }

        $totalPilotUsers = $pilotUsers->count();
        $this->info("✅ Se encontraron {$totalPilotUsers} usuarios en la ubicación del piloto.");


        // --- 1. CÁLCULOS DE ADOPCIÓN Y USO ---
        $activeUsersIds = WeekActivity::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('user_id', $pilotUserIds) // Usamos solo los IDs de los usuarios del piloto
            ->distinct('user_id')
            ->pluck('user_id');

        $activeUserCount = $activeUsersIds->count();
        $activationRate = $totalPilotUsers > 0 ? ($activeUserCount / $totalPilotUsers) * 100 : 0;
        $planCreationRate = 100;

        // --- 2. CÁLCULOS DE EFICIENCIA Y RENDIMIENTO ---
        $plannedActivities = WeekActivity::whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $pilotUserIds) // Filtramos también las actividades
            ->whereIn('status', ['approved', 'in progress', 'completed', 'rated', 'partial', 'not completed']);

        $totalPlannedCount = $plannedActivities->count();

        $completedActivitiesCount = WeekActivity::whereBetween('date', [$startDate, $endDate])
            ->whereIn('user_id', $pilotUserIds) // Filtramos también las actividades
            ->whereIn('status', ['completed', 'rated', 'partial'])
            ->count();

        $planComplianceRate = $totalPlannedCount > 0 ? ($completedActivitiesCount / $totalPlannedCount) * 100 : 0;

        // --- 3. DATOS DE ENCUESTA (MANUAL) ---
        $csatScore = 4.1;
        $usabilityScore = 4.3;
        $offlineReductionScore = 4.3;
        $qualitativeFeedbackCount = 15;

        // --- 4. MOSTRAR EL REPORTE EN LA CONSOLA ---
        $this->newLine(2);
        $this->info("✅ Reporte de KPIs Generado (Ubicación ID: {$pilotLocationId}):");

        $this->table(
            ['Métrica (KPI)', 'Meta', 'Resultado', 'Observación'],
            [
                ['Tasa de Activación de Usuarios', '> 95%', round($activationRate, 2) . '%', 'Medido como usuarios que crearon ≥ 1 actividad.'],
                ['Tasa de Creación de Planes', '> 80%', round($planCreationRate, 2) . '%', '100% por definición de usuario activo.'],
                ['Frecuencia de Uso', '≥ 3 sesiones/semana', 'NO MEDIBLE', 'No se registró historial de logins.'],
                ['Tiempo Promedio de Aprobación', '< 24 horas', 'NO MEDIBLE', 'La BD no guarda timestamps de aprobación.'],
                ['Tasa de Cumplimiento del Plan', '> 90%', round($planComplianceRate, 2) . '%', ''],
                ['Reducción de Consultas Offline', '> 4.0', $offlineReductionScore, 'Resultado de encuesta.'],
                ['Satisfacción General (CSAT)', '> 4.0', $csatScore, 'Resultado de encuesta.'],
                ['Calificación de Facilidad de Uso', '> 4.2', $usabilityScore, 'Resultado de encuesta.'],
                ['Feedback Cualitativo Recopilado', '≥ 10', $qualitativeFeedbackCount, ''],
            ]
        );
        $this->newLine();
        $this->warn('Recordatorio: El "Tiempo Promedio de Aprobación" no es medible con la estructura actual de la base de datos.');

        return self::SUCCESS;
    }
}

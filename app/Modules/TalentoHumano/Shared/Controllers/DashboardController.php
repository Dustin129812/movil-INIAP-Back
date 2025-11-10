<?php

namespace App\Modules\TalentoHumano\Shared\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TalentoHumano\HorasExtras\Models\ReporteMensualHE;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Alerta: Muestra los reportes que DAF acaba de aprobar
     * Requerimiento: "Alertas de que DAF aprobó el reporte mensual"
     */
    public function getAlertasAprobadosDAF()
    {
        // Muestra reportes aprobados en los últimos 7 días
        $reportesRecientes = ReporteMensualHE::where('estado', 'aprobado')
            ->where('aprobado_daf_at', '>=', Carbon::now()->subDays(7))
            ->with(['conductor:id,name', 'daf:id,name'])
            ->select('id', 'user_id', 'daf_id', 'mes', 'anio', 'monto_total_pagar', 'aprobado_daf_at')
            ->orderBy('aprobado_daf_at', 'desc')
            ->get();

        return response()->json($reportesRecientes);
    }

    /**
     * Alerta: Muestra quién NO ha puesto sus horas
     * Requerimiento: "Alertas de quien no puso sus horas"
     */
    public function getAlertasPendientesRegistro()
    {
        // Define el rango de tiempo (ej. la semana actual)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        // 1. Obtiene los IDs de los conductores que SÍ registraron horas esta semana
        $conductoresConRegistro = DB::table('registro_horas')
            ->whereBetween('fecha', [$startOfWeek, $endOfWeek])
            ->distinct()
            ->pluck('user_id');

        // 2. Busca todos los usuarios con el ROL 'TH Conductor'
        //    que NO estén en la lista de los que sí registraron.
        $conductoresSinRegistro = User::role('TH Conductor') // (Asegúrate que el rol se llame así)
        ->whereNotIn('id', $conductoresConRegistro)
            ->get(['id', 'name', 'dni']);

        return response()->json($conductoresSinRegistro);
    }
}

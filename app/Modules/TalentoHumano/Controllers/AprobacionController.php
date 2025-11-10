<?php

namespace App\Modules\TalentoHumano\HorasExtras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TalentoHumano\HorasExtras\Models\RegistroHora;
use App\Modules\TalentoHumano\HorasExtras\Models\ReporteMensualHE;
use App\Modules\TalentoHumano\HorasExtras\Services\CalculoHorasService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Response;

class AprobacionController extends Controller
{
    protected $calculoService;

    // Inyectamos el servicio de cálculo
    public function __construct(CalculoHorasService $calculoService)
    {
        $this->calculoService = $calculoService;
    }

    /**
     * Tarea programada (Scheduler) o endpoint que genera los reportes mensuales.
     * Requerimiento: "Vladimir revisa y envía el reporte mensual de cada conductor"
     * * Este método se encarga de "enviar". Lo llamaremos desde un endpoint de "cierre de mes".
     * Opcionalmente: se puede ejecutar automáticamente el día 1 de cada mes.
     */
    public function generarReportesMensuales(Request $request)
    {
        // Genera reportes para el mes anterior
        $mes = Carbon::now()->subMonth()->month;
        $anio = Carbon::now()->subMonth()->year;

        // 1. Encontrar todos los usuarios (conductores) con registros 'registrado'
        $conductoresIds = RegistroHora::where('estado', 'registrado')
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->distinct()
            ->pluck('user_id');

        if ($conductoresIds->isEmpty()) {
            return response()->json(['message' => 'No hay horas pendientes para generar reportes.'], Response::HTTP_OK);
        }

        $reportesCreados = 0;
        DB::beginTransaction();
        try {
            foreach ($conductoresIds as $userId) {
                // 2. Obtener el usuario (para el sueldo) y sus registros
                $conductor = User::find($userId);
                if (!$conductor || $conductor->sueldo <= 0) {
                    // Log::warning("Usuario $userId no tiene sueldo, se omite reporte de HE.");
                    continue; // Omite si no tiene sueldo
                }

                $registrosDelMes = RegistroHora::where('user_id', $userId)
                    ->where('estado', 'registrado')
                    ->whereMonth('fecha', $mes)
                    ->whereYear('fecha', $anio)
                    ->get();

                if ($registrosDelMes->isEmpty()) {
                    continue;
                }

                // 3. Usar el Servicio de Cálculo
                $calculos = $this->calculoService->calcularMontos($conductor->sueldo, $registrosDelMes);

                // 4. Crear el Reporte Mensual
                $reporte = ReporteMensualHE::create([
                    'user_id' => $userId,
                    'mes' => $mes,
                    'anio' => $anio,
                    'estado' => 'pendiente_jefe', // Listo para Vladimir
                    'jefe_id' => null, // Se asigna al aprobar
                    'daf_id' => null,  // Se asigna al aprobar
                    // Llenar con los datos del servicio
                    'total_horas_suplementarias' => $calculos['total_horas_suplementarias'],
                    'total_horas_extraordinarias' => $calculos['total_horas_extraordinarias'],
                    'monto_suplementarias' => $calculos['monto_suplementarias'],
                    'monto_extraordinarias' => $calculos['monto_extraordinarias'],
                    'monto_fondos_reserva' => $calculos['monto_fondos_reserva'],
                    'monto_decimo_tercero' => $calculos['monto_decimo_tercero'], // Nombre corregido
                    'monto_total_pagar' => $calculos['monto_total_pagar'],
                ]);

                // 5. Actualizar los registros individuales
                RegistroHora::whereIn('id', $registrosDelMes->pluck('id'))
                    ->update([
                        'estado' => 'en_revision', // Cambia estado de registros
                        'reporte_mensual_he_id' => $reporte->id // Los vincula al reporte
                    ]);

                $reportesCreados++;
            }

            DB::commit();
            return response()->json(['message' => "Se generaron $reportesCreados reportes mensuales."], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al generar reportes.', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // --- Métodos para Jefe Inmediato (Vladimir) ---

    public function getPendientesJefe()
    {
        $reportes = ReporteMensualHE::where('estado', 'pendiente_jefe')
            ->with('conductor:id,name') // Carga el nombre del conductor
            ->select('id', 'user_id', 'mes', 'anio', 'monto_total_pagar', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reportes);
    }

    public function getRegistrosDelReporte(ReporteMensualHE $reporte)
    {
        // Seguridad: Opcional, verificar que el jefe pueda ver este reporte

        // Carga los registros individuales vinculados a este reporte
        $registros = $reporte->registros()->with('vehiculo:id,placa')->get();

        return response()->json([
            'reporte' => $reporte->load('conductor:id,name'),
            'registros' => $registros
        ]);
    }

    public function aprobarJefe(Request $request, ReporteMensualHE $reporte)
    {
        $userJefe = Auth::user();

        if ($reporte->estado !== 'pendiente_jefe') {
            return response()->json(['error' => 'Este reporte no está pendiente de aprobación.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reporte->update([
            'estado' => 'pendiente_daf', // Siguiente paso
            'jefe_id' => $userJefe->id,
            'aprobado_jefe_at' => Carbon::now(),
        ]);

        // (Opcional: Disparar evento/notificación para Majo)

        return response()->json(['message' => 'Reporte aprobado y enviado a DAF.']);
    }

    public function rechazarJefe(Request $request, ReporteMensualHE $reporte)
    {
        // (Lógica de rechazo: similar a aprobar, pero cambia estado a 'rechazado_jefe')
        // ...
        return response()->json(['message' => 'Reporte rechazado.']);
    }


    // --- Métodos para DAF (Majo) ---

    public function getPendientesDAF()
    {
        $reportes = ReporteMensualHE::where('estado', 'pendiente_daf')
            ->with(['conductor:id,name', 'jefe:id,name']) // Carga conductor y jefe
            ->select('id', 'user_id', 'jefe_id', 'mes', 'anio', 'monto_total_pagar', 'aprobado_jefe_at')
            ->orderBy('aprobado_jefe_at', 'desc')
            ->get();

        return response()->json($reportes);
    }

    public function aprobarDAF(Request $request, ReporteMensualHE $reporte)
    {
        $userDAF = Auth::user();

        if ($reporte->estado !== 'pendiente_daf') {
            return response()->json(['error' => 'Este reporte no está pendiente de aprobación DAF.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reporte->update([
            'estado' => 'aprobado', // Aprobación Final
            'daf_id' => $userDAF->id,
            'aprobado_daf_at' => Carbon::now(),
        ]);

        // Requerimiento: "Alertas de que DAF aprobó el reporte mensual"
        // Aquí disparamos el evento o notificación para DTH
        // event(new ReporteAprobadoPorDAF($reporte));

        return response()->json(['message' => 'Reporte aprobado exitosamente.']);
    }

    public function rechazarDAF(Request $request, ReporteMensualHE $reporte)
    {
        // (Lógica de rechazo: similar a aprobar, pero cambia estado a 'rechazado_daf')
        // ...
        return response()->json(['message' => 'Reporte rechazado.']);
    }
}

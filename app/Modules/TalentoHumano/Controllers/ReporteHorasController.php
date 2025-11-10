<?php

namespace App\Modules\TalentoHumano\HorasExtras\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TalentoHumano\HorasExtras\Models\ReporteMensualHE;
use App\Modules\TalentoHumano\HorasExtras\Models\RegistroHora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class ReporteHorasController extends Controller
{
    /**
     * Genera reporte individual mensual (PDF/EXCEL CON firmas)
     * Requerimiento: Devuelve los datos de un reporte mensual APROBADO.
     * La generación del archivo (PDF/Excel) se maneja en el frontend o con un paquete.
     */
    public function generarIndividual(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020',
        ]);

        $reporte = ReporteMensualHE::where('user_id', $request->user_id)
            ->where('mes', $request->mes)
            ->where('anio', $request->anio)
            ->where('estado', 'aprobado') // Solo reportes aprobados
            ->with([
                'conductor:id,name,dni,sueldo',
                'jefe:id,name',
                'daf:id,name',
                'registros'
            ])
            ->first();

        if (!$reporte) {
            return response()->json(['error' => 'No se encontró un reporte aprobado para este conductor en esa fecha.'], Response::HTTP_NOT_FOUND);
        }

        // Aquí pasarías $reporte a tu clase de exportación (Excel o PDF)
        return response()->json($reporte);
    }

    /**
     * Genera reporte individual por rango de fechas (PDF/EXCEL SIN firmas)
     * Requerimiento: Busca en REGISTROS, no en reportes mensuales.
     */
    public function generarPorRango(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'user_id' => 'sometimes|integer|exists:users,id', // Opcional
        ]);

        $query = RegistroHora::whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin])
            ->with(['conductor:id,name,dni', 'vehiculo:id,placa'])
            ->orderBy('user_id')->orderBy('fecha');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $registros = $query->get();

        // Aquí pasarías $registros a tu clase de exportación
        return response()->json($registros);
    }

    /**
     * Genera reporte de cuanto se ha pagado (monetario y horas)
     * Requerimiento: Consulta sobre REPORTES MENSUALES APROBADOS.
     */
    public function generarResumenPago(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        // Convertimos las fechas a mes/año para la consulta
        $inicioMes = (int)date('m', strtotime($request->fecha_inicio));
        $inicioAnio = (int)date('Y', strtotime($request->fecha_inicio));
        $finMes = (int)date('m', strtotime($request->fecha_fin));
        $finAnio = (int)date('Y', strtotime($request->fecha_fin));

        $resumen = ReporteMensualHE::where('estado', 'aprobado')
            ->where(function ($query) use ($inicioAnio, $inicioMes) {
                $query->where('anio', '>', $inicioAnio)
                    ->orWhere(function ($q) use ($inicioAnio, $inicioMes) {
                        $q->where('anio', $inicioAnio)
                            ->where('mes', '>=', $inicioMes);
                    });
            })
            ->where(function ($query) use ($finAnio, $finMes) {
                $query->where('anio', '<', $finAnio)
                    ->orWhere(function ($q) use ($finAnio, $finMes) {
                        $q->where('anio', $finAnio)
                            ->where('mes', '>=', $finMes);
                    });
            })
            ->select(
                DB::raw('SUM(total_horas_suplementarias) as gran_total_horas_suplementarias'),
                DB::raw('SUM(total_horas_extraordinarias) as gran_total_horas_extraordinarias'),
                DB::raw('SUM(monto_suplementarias) as gran_total_monto_suplementarias'),
                DB::raw('SUM(monto_extraordinarias) as gran_total_monto_extraordinarias'),
                DB::raw('SUM(monto_total_pagar) as gran_total_pagado')
            )
            ->first();

        return response()->json($resumen);
    }
}

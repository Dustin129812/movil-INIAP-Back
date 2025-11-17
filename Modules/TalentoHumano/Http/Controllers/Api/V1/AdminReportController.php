<?php

namespace Modules\TalentoHumano\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\TalentoHumano\Entities\ThOvertimeReport;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;

// TODO: Importar el servicio de PDF cuando lo creemos
// use Modules\TalentoHumano\Services\PdfReportGeneratorService;

class AdminReportController extends Controller
{
    /**
     * Muestra una lista paginada de TODOS los reportes para el admin.
     * GET /api/v1/talento-humano/admin/reports
     */
    public function index(Request $request): JsonResponse
    {
        $query = ThOvertimeReport::query()->with('driver:id,name');

        if ($request->has('driver_id')) {
            $query->where('driver_id', $request->input('driver_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }
        if ($request->has('month')) {
            $query->where('month', $request->input('month'));
        }

        $reports = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('submitted_at', 'desc')
            ->paginate(25);

        return response()->json($reports);
    }

    /**
     * Muestra un reporte específico (para el admin).
     * GET /api/v1/talento-humano/reports/{report} (Ruta compartida)
     */
    public function show(ThOvertimeReport $report): JsonResponse
    {
        $report->load('driver', 'entries.activityType', 'entries.vehicle');
        return response()->json($report);
    }


    /**
     * Genera el PDF de un reporte (para el admin).
     * GET /api/v1/talentoHumano/reports/{report}/download-pdf
     */
    public function downloadPdf(ThOvertimeReport $report)
    {
        $report->load([
            'driver.position',
            'driver.location',
            'entries.activityType',
            'supervisorApprover:id,name',
            'dafApprover:id,name'
        ]);

        $filename = "solicitud_horas_extras_{$report->driver->name}_{$report->month}_{$report->year}.pdf";

        $pdf = PDF::loadView('talentohumano::reports.overtime_report', [
            'report' => $report
        ]);

        return $pdf->stream($filename);
    }


    /**
     * Devuelve data agregada para el Dashboard.
     * GET /api/v1/talento-humano/admin/analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);

        $usdPerMonth = ThOvertimeReport::where('year', $year)
            ->where('status', 'aprobado')
            ->select(
                DB::raw('month(submitted_at) as month'),
                DB::raw('SUM(total_usd_pay) as total_usd')
            )
            ->groupBy('month')
            ->pluck('total_usd', 'month');

        $totalMinutes = ThOvertimeReport::where('year', $year)
            ->where('status', 'aprobado')
            ->select(
                DB::raw('SUM(total_supplemental_minutes) as total_supplemental'),
                DB::raw('SUM(total_extraordinary_minutes) as total_extraordinary')
            )
            ->first();

        return response()->json([
            'usd_per_month' => $usdPerMonth,
            'total_hours' => [
                'supplemental' => $totalMinutes->total_supplemental ?? 0,
                'extraordinary' => $totalMinutes->total_extraordinary ?? 0,
            ],
        ]);
    }

    /**
     * Marca un reporte como Aprobado (paso final de DATH).
     * POST /api/v1/talento-humano/admin/reports/{report}/finalize
     */
    public function finalize(Request $request, ThOvertimeReport $report): JsonResponse
    {
        if ($report->status !== 'pendiente_dath') {
            return response()->json([
                'message' => 'Este reporte no está pendiente de aprobación de DATH.'
            ], 409);
        }

        $report->update([
            'status' => 'aprobado',
            // 'dath_approver_id' => Auth::id(), // Podríamos añadir esta columna
            // 'dath_approved_at' => now(),       // Podríamos añadir esta columna
            'rejection_reason' => null,
        ]);

        // TODO: Disparar evento de Reporte Completado (para nómina, etc.)
        // event(new ReportFinalized($report));

        return response()->json($report);
    }

    /**
     * Genera el PDF de REPORTE DE PAGO (para DATH).
     * GET /api/v1/talentoHumano/admin/reports/{report}/download-payment-report
     */
    public function downloadPaymentReport(ThOvertimeReport $report)
    {
        if ($report->status !== 'aprobado') {
            return response()->json([
                'message' => 'Solo se pueden generar reportes de pago para solicitudes aprobadas.'
            ], 409);
        }

        $report->load([
            'driver.position',
            'driver.location',
            'entries.activityType',
            'dafApprover:id,name'
        ]);

        $filename = "reporte_pago_{$report->driver->name}_{$report->month}_{$report->year}.pdf";

        $pdf = PDF::loadView('talentohumano::reports.payment_report', [
            'report' => $report
        ]);

        return $pdf->stream($filename);
    }

}

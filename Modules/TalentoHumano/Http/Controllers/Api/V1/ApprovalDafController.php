<?php

namespace Modules\TalentoHumano\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\TalentoHumano\Entities\ThOvertimeReport;
use Modules\TalentoHumano\Http\Requests\DafRejectReportRequest;
use Illuminate\Http\JsonResponse;

class ApprovalDafController extends Controller
{
    /**
     * Muestra los reportes pendientes de aprobación de la DAF.
     * GET /api/v1/talento-humano/approvals/daf
     */
    public function index(): JsonResponse
    {
        $reports = ThOvertimeReport::with('driver:id,name', 'entries')
        ->where('status', 'pendiente_daf')
            ->orderBy('supervisor_approved_at', 'asc')
            ->paginate(15);

        return response()->json($reports);
    }

    /**
     * Aprueba un reporte.
     * POST /api/v1/talento-humano/approvals/daf/{report}/approve
     */
    public function approve(Request $request, ThOvertimeReport $report): JsonResponse
    {
        // 1. Validar estado
        if ($report->status !== 'pendiente_daf') {
            return response()->json([
                'message' => 'Este reporte no está pendiente de aprobación de DAF.'
            ], 409); // 409 Conflict
        }

        $report->update([
            'status' => 'pendiente_dath',
            'daf_approver_id' => Auth::id(),
            'daf_approved_at' => now(),
            'rejection_reason' => null,
        ]);

        // TODO: Disparar evento/notificación para DATH (Talento Humano)
        // event(new ReportApprovedByDaf($report));

        return response()->json($report);
    }

    /**
     * Rechaza un reporte.
     * POST /api/v1/talento-humano/approvals/daf/{report}/reject
     */
    public function reject(DafRejectReportRequest $request, ThOvertimeReport $report): JsonResponse
    {

        $report->update([
            'status' => 'borrador',
            'rejection_reason' => $request->validated()['rejection_reason'],

            'supervisor_approver_id' => null,
            'supervisor_approved_at' => null,
            'daf_approver_id' => null,
            'daf_approved_at' => null,
        ]);

        // TODO: Disparar evento/notificación para el Conductor
        // event(new ReportRejected($report));

        return response()->json($report);
    }
}

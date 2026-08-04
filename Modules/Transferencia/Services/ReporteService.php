<?php

namespace Modules\Transferencia\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Modules\Transferencia\Entities\Ensayo;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ReporteService
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function generarReporteDashboard(int $userId, bool $canSeeAll, array $filters)
    {
        $metricas = $this->dashboardService->getMetricasGlobales('dummy', $userId, $canSeeAll, $filters);

        $ensayoQuery = Ensayo::query()
            ->whereNotNull('producto_id')
            ->with([
                'producto:id,name',
                'location:id,name',
                'equipoTecnico:id,name',
                'parcelas' => function ($q) {
                    $q->select('id', 'ensayo_id', 'nombre', 'estado', 'provincia_id', 'canton_id', 'parroquia_id', 'localidad')
                        ->with(['provincia:id,name', 'canton:id,name', 'parroquia:id,nombre']);
                }
            ]);

        if (!$canSeeAll) {
            $ensayoQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('equipoTecnico', fn($sq) => $sq->where('users.id', $userId));
            });
        } else {
            if (!empty($filters['location_id'])) $ensayoQuery->where('location_id', $filters['location_id']);
            if (!empty($filters['filter_user_id'])) {
                $target = $filters['filter_user_id'];
                $ensayoQuery->where(function ($q) use ($target) {
                    $q->where('user_id', $target)->orWhereHas('equipoTecnico', fn($sq) => $sq->where('users.id', $target));
                });
            }
        }

        $poasDetallados = $ensayoQuery->get()->groupBy('producto_id');

        $totalPersonas = $metricas['kpis']['impacto_productores'] ?? 0;
        $pctHombres = $totalPersonas > 0 ? round(($metricas['demografia']['hombres'] / $totalPersonas) * 100, 2) : 0;
        $pctMujeres = $totalPersonas > 0 ? round(($metricas['demografia']['mujeres'] / $totalPersonas) * 100, 2) : 0;

        $estados = $metricas['estados_parcelas'];
        $finalizadas = $estados['Finalizado'] ?? 0;
        $perdidas = ($estados['Perdido'] ?? 0) + ($estados['Dado de baja'] ?? 0);
        $totalCerradas = $finalizadas + $perdidas;
        $tasaEficiencia = $totalCerradas > 0 ? round(($finalizadas / $totalCerradas) * 100, 1) : 0;

        $qrToken = Str::uuid()->toString();
        $idReporte = 'REP-' . strtoupper(substr($qrToken, 0, 8));
        $baseUrl = config('app.frontend_url', env('APP_URL', 'http://localhost:3000'));
        $urlValidacion = rtrim($baseUrl, '/') . '/validar-documento/' . $qrToken;

        $renderer = new ImageRenderer(new RendererStyle(90), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrCodeBase64 = base64_encode($writer->writeString($urlValidacion));

        $data = [
            'metricas' => $metricas,
            'poas_detallados' => $poasDetallados,

            'porcentajes' => [
                'hombres' => $pctHombres,
                'mujeres' => $pctMujeres,
            ],

            'eficiencia' => [
                'tasa' => $tasaEficiencia,
                'estado' => $tasaEficiencia < 80 ? 'crítica' : 'óptima',
                'perdidas' => $perdidas
            ],
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'generado_por' => request()->user()->name ?? 'Sistema SIMPAGI',
            'qr_code' => $qrCodeBase64,
            'id_reporte' => $idReporte
        ];

        $pdf = Pdf::loadView('transferencia::reportes.dashboard_ejecutivo', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("Planificacion_Estrategica_Campo_{$idReporte}.pdf");
    }
}

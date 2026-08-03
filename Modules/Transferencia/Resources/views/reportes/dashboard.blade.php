<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Gerencial de Transferencia</title>
    <style>
        /* Estilos optimizados para DomPDF */
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #334155; margin: 0; font-size: 12px; }

        /* Cabecera */
        .header { border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 25px; }
        .header table { width: 100%; }
        .header .title { font-size: 20px; font-weight: bold; color: #0f172a; margin: 0 0 5px 0; }
        .header .subtitle { font-size: 11px; color: #64748b; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .meta-data { font-size: 10px; color: #64748b; margin-top: 10px; }

        /* Secciones */
        .section-title { font-size: 14px; font-weight: bold; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; text-transform: uppercase; }

        /* Grillas simuladas con Tablas */
        .grid-table { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-bottom: 20px; }
        .kpi-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; }
        .kpi-value { font-size: 24px; font-weight: bold; color: #10b981; margin: 5px 0; }
        .kpi-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: bold; }

        /* Tablas de Datos */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        .data-table th { background-color: #f1f5f9; color: #475569; font-weight: bold; text-align: left; padding: 10px; border-bottom: 2px solid #cbd5e1; }
        .data-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .data-table tr:nth-child(even) { background-color: #f8fafc; }

        /* Barras de Progreso CSS */
        .progress-container { width: 100%; background-color: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 5px; height: 12px; }
        .progress-bar-men { float: left; height: 100%; background-color: #0ea5e9; }
        .progress-bar-women { float: left; height: 100%; background-color: #d946ef; }
        .demographic-labels { width: 100%; font-size: 10px; font-weight: bold; margin-bottom: 5px; }
        .label-men { color: #0ea5e9; }
        .label-women { color: #d946ef; float: right; }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td style="width: 70%;">
                <h1 class="title">Matriz de Transferencia e Innovación</h1>
                <p class="subtitle">Instituto Nacional de Investigaciones Agropecuarias (INIAP)</p>
                <div class="meta-data">
                    <strong>Generado por:</strong> {{ $generado_por }}<br>
                    <strong>Fecha de Emisión:</strong> {{ $fecha_generacion }}<br>
                    <strong>Alcance del Reporte:</strong> {{ $filtros_texto }}
                </div>
            </td>
            <td style="width: 30%; text-align: right;">
                <span style="font-size: 24px; color: #10b981; font-weight: bold;">SIMPAGI</span>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Resumen Ejecutivo</div>
<table class="grid-table">
    <tr>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-label">Ensayos Activos</div>
            <div class="kpi-value">{{ number_format($metricas['kpis']['ensayos_activos']) }}</div>
        </td>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-label">Parcelas Desplegadas</div>
            <div class="kpi-value" style="color: #f97316;">{{ number_format($metricas['kpis']['parcelas_desplegadas']) }}</div>
        </td>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-label">Productores Alcanzados</div>
            <div class="kpi-value" style="color: #0ea5e9;">{{ number_format($metricas['kpis']['impacto_productores']) }}</div>
        </td>
        <td class="kpi-card" style="width: 25%;">
            <div class="kpi-label">Acuerdos Vigentes</div>
            <div class="kpi-value" style="color: #a855f7;">{{ number_format($metricas['kpis']['acuerdos_vigentes']) }}</div>
        </td>
    </tr>
</table>

<table style="width: 100%; border-spacing: 20px 0; border-collapse: separate;">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div class="section-title">Impacto Demográfico y Social</div>
            <div style="background: #f8fafc; padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <p style="margin: 0 0 15px 0; font-size: 11px;">Distribución basada en el censo de participantes de las organizaciones vinculadas a los acuerdos de transferencia.</p>

                <div class="demographic-labels">
                    <span class="label-men">{{ $porcentajes['hombres'] }}% Hombres ({{ number_format($metricas['demografia']['hombres']) }})</span>
                    <span class="label-women">{{ $porcentajes['mujeres'] }}% Mujeres ({{ number_format($metricas['demografia']['mujeres']) }})</span>
                </div>

                <div class="progress-container">
                    <div class="progress-bar-men" style="width: {{ $porcentajes['hombres'] }}%;"></div>
                    <div class="progress-bar-women" style="width: {{ $porcentajes['mujeres'] }}%;"></div>
                </div>
            </div>

            <div class="section-title">Salud Operativa de Parcelas</div>
            <table class="data-table">
                <thead>
                <tr>
                    <th>Estado de Parcela</th>
                    <th style="text-align: center;">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($metricas['estados_parcelas'] as $estado => $total)
                    <tr>
                        <td>{{ $estado }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ number_format($total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="text-align: center;">No hay parcelas registradas</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>

        <td style="width: 50%; vertical-align: top;">
            <div class="section-title">Huella Territorial (Top Provincias)</div>
            <table class="data-table">
                <thead>
                <tr>
                    <th>Provincia</th>
                    <th style="text-align: center;">Total Parcelas</th>
                </tr>
                </thead>
                <tbody>
                @forelse($metricas['huella_territorial'] as $loc)
                    <tr>
                        <td>{{ $loc['provincia'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ number_format($loc['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="text-align: center;">Sin despliegue geográfico</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

<div class="section-title" style="margin-top: 10px;">Alineación Estratégica: Top Productos POA</div>
<table class="data-table">
    <thead>
    <tr>
        <th style="width: 80%;">Nombre del Producto POA</th>
        <th style="width: 20%; text-align: center;">Ensayos Asignados</th>
    </tr>
    </thead>
    <tbody>
    @forelse($metricas['top_poas'] as $poa)
        <tr>
            <td>{{ $poa['nombre'] }}</td>
            <td style="text-align: center; font-weight: bold; color: #10b981;">{{ number_format($poa['total_ensayos']) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="2" style="text-align: center; color: #64748b;">No existen productos POA activos en este segmento.</td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>

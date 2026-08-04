<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inteligencia y Despliegue de Campo</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1e293b; margin: 0; font-size: 10px; background-color: #ffffff; line-height: 1.3; }

        .w-100 { width: 100%; }
        .mb-4 { margin-bottom: 15px; }
        .page-break { page-break-before: always; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Encabezado Oficial */
        .header-box { border-bottom: 3px solid #059669; padding-bottom: 8px; margin-bottom: 15px; }
        .title { font-size: 18px; font-weight: 900; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: -0.3px; }
        .subtitle { font-size: 10px; color: #64748b; margin: 2px 0 0 0; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Tarjetas Bento KPIs */
        .metric-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; text-align: center; padding: 10px 4px; }
        .metric-value { font-size: 20px; font-weight: 900; color: #059669; margin-bottom: 2px; }
        .metric-label { font-size: 8px; text-transform: uppercase; color: #475569; font-weight: bold; }

        /* Bloques de Alerta e Insights */
        .insight-box { padding: 10px; border-left: 4px solid #059669; background-color: #f8fafc; font-size: 10px; margin-bottom: 15px; }
        .insight-critical { border-color: #e11d48; background-color: #fff1f2; }

        .section-title { font-size: 11px; font-weight: 900; color: #0f172a; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; padding-bottom: 3px; margin-bottom: 10px; letter-spacing: 0.3px; }

        /* Estructura de Tablas de Alta Densidad */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th { background-color: #0f172a; color: #ffffff; font-size: 8px; text-transform: uppercase; padding: 8px 6px; text-align: left; font-weight: bold; border: 1px solid #0f172a; }
        .data-table td { padding: 6px; font-size: 9.5px; border: 1px solid #e2e8f0; color: #334155; vertical-align: middle; }
        .data-table tr:nth-child(even) { background-color: #f8fafc; }

        /* Gráficos de barra integrados en tablas */
        .bar-bg { width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; margin-top: 2px; }
        .bar-fill { height: 100%; background-color: #059669; }
        .bar-men { background-color: #0ea5e9; }
        .bar-women { background-color: #d946ef; }

        /* Badges de Estado */
        .badge { padding: 2px 4px; border-radius: 4px; font-size: 7.5px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .badge-active { background-color: #dcfce7; color: #15803d; }
        .badge-harvested { background-color: #f3e8ff; color: #6b21a8; }
        .badge-lost { background-color: #fee2e2; color: #991b1b; }
        .badge-queue { background-color: #fef3c7; color: #92400e; }

        .poa-header { background-color: #1e293b; color: #ffffff; padding: 6px 10px; font-size: 10px; font-weight: bold; border-radius: 4px; margin-top: 12px; }
    </style>
</head>
<body>

<table class="w-100 header-box">
    <tr>
        <td style="width: 85%;">
            <h1 class="title">Reporte Ejecutivo de Inteligencia Agro-Territorial</h1>
            <p class="subtitle">Consola de Evaluación Operativa y Auditoría de Campo - SIMPAGI</p>
            <div style="margin-top: 5px; font-size: 9px; color: #475569;">
                <strong>Evaluador Institucional:</strong> {{ $generado_por }} &nbsp;|&nbsp;
                <strong>Corte de Datos:</strong> {{ $fecha_generacion }} &nbsp;|&nbsp;
                <strong>Sincronización:</strong> Base de Datos PostgreSQL Activa
            </div>
        </td>
        <td style="width: 15%; text-align: right;">
            <img src="data:image/svg+xml;base64,{{ $qr_code }}" style="max-height: 60px;">
            <div style="font-size: 7px; color: #94a3b8; margin-top: 1px;">{{ $id_reporte }}</div>
        </td>
    </tr>
</table>

<table class="w-100 mb-4" style="border-spacing: 6px 0; border-collapse: separate; margin-left: -6px; width: calc(100% + 12px);">
    <tr>
        <td class="metric-card" style="width: 25%;">
            <div class="metric-value">{{ number_format($metricas['kpis']['ensayos_activos']) }}</div>
            <div class="metric-label">Líneas de Ensayo</div>
        </td>
        <td class="metric-card" style="width: 25%;">
            <div class="metric-value" style="color: #ea580c;">{{ number_format($metricas['kpis']['parcelas_desplegadas']) }}</div>
            <div class="metric-label">Parcelas en Campo</div>
        </td>
        <td class="metric-card" style="width: 25%;">
            <div class="metric-value" style="color: #0284c7;">{{ number_format($metricas['kpis']['impacto_productores']) }}</div>
            <div class="metric-label">Beneficiarios Totales</div>
        </td>
        <td class="metric-card" style="width: 25%;">
            <div class="metric-value" style="color: #7c3aed;">{{ number_format($metricas['kpis']['acuerdos_vigentes']) }}</div>
            <div class="metric-label">Acuerdos Firmados</div>
        </td>
    </tr>
</table>

<div class="section-title">Análisis Granular de Impacto Social y Equidad de Género</div>
<table class="data-table mb-4">
    <thead>
    <tr>
        <th style="width: 20%;">Segmento de Control</th>
        <th style="width: 20%; text-align: center;">Conteo Absoluto (Enteros)</th>
        <th style="width: 20%; text-align: center;">Participación Relativa</th>
        <th style="width: 40%;">Distribución Geo-Exponencial en Territorio</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><strong>Hombres Beneficiarios</strong></td>
        <td class="text-center font-bold" style="color: #0284c7;">{{ number_format($metricas['demografia']['hombres']) }} productores</td>
        <td class="text-center font-bold">{{ number_format($porcentajes['hombres'], 2) }}%</td>
        <td>
            <div class="bar-bg"><div class="bar-fill bar-men" style="width: {{ $porcentajes['hombres'] }}%;"></div></div>
        </td>
    </tr>
    <tr>
        <td><strong>Mujeres Beneficiarias</strong></td>
        <td class="text-center font-bold" style="color: #g946ef;">{{ number_format($metricas['demografia']['mujeres']) }} productoras</td>
        <td class="text-center font-bold">{{ number_format($porcentajes['mujeres'], 2) }}%</td>
        <td>
            <div class="bar-bg"><div class="bar-fill bar-women" style="width: {{ $porcentajes['mujeres'] }}%;"></div></div>
        </td>
    </tr>
    <tr style="background-color: #f1f5f9; font-weight: bold;">
        <td>Consolidado General</td>
        <td class="text-center" style="color: #0f172a;">{{ number_format($metricas['kpis']['impacto_productores']) }} personas</td>
        <td class="text-center">100.00%</td>
        <td style="color: #475569; font-size: 8.5px; font-style: italic;">Muestreo censal absoluto derivado de organizaciones aliadas.</td>
    </tr>
    </tbody>
</table>

<table class="w-100" style="border-spacing: 12px 0; border-collapse: separate; margin-left: -12px; width: calc(100% + 24px);">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div class="section-title">Estatus General de la Infraestructura Agrícola</div>
            <table class="data-table">
                <thead>
                <tr>
                    <th style="width: 40%;">Fase de Cultivo / Estado</th>
                    <th style="width: 25%; text-align: center;">Volumen</th>
                    <th style="width: 35%; text-align: center;">Peso Relativo Nacional</th>
                </tr>
                </thead>
                <tbody>
                @forelse($metricas['estados_parcelas'] as $estado => $total)
                    @php
                        $pesoEstado = $metricas['kpis']['parcelas_desplegadas'] > 0 ? round(($total / $metricas['kpis']['parcelas_desplegadas']) * 100, 2) : 0;
                    @endphp
                    <tr>
                        <td>
                                    <span class="badge
                                        @if($estado === 'Implementado') badge-active
                                        @elseif($estado === 'Finalizado') badge-harvested
                                        @elseif($estado === 'Perdido' || $estado === 'Dado de baja') badge-lost
                                        @else badge-queue @endif">
                                        {{ $estado }}
                                    </span>
                        </td>
                        <td class="text-center font-bold">{{ number_format($total) }} ud.</td>
                        <td class="text-center font-bold text-slate-600">{{ $pesoEstado }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-slate-400">Sin infraestructura reportada.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="insight-box {{ $eficiencia['tasa'] >= 80 ? '' : 'insight-critical' }}" style="margin-top: 5px;">
                <strong>Eficiencia Operativa Regional: {{ number_format($eficiencia['tasa'], 1) }}%</strong><br>
                Tasa calculada sobre parcelas con ciclo cerrado (Cosechadas vs Bajas críticas). Estatus actual: <strong>{{ strtoupper($eficiencia['estado']) }}</strong>.
            </div>
        </td>

        <td style="width: 50%; vertical-align: top;">
            <div class="section-title">Dispersión Territorial de Parcelas</div>
            <table class="data-table">
                <thead>
                <tr>
                    <th style="width: 40%;">Provincia Evaluada</th>
                    <th style="width: 25%; text-align: center;">Densidad</th>
                    <th style="width: 35%;">Carga Territorial</th>
                </tr>
                </thead>
                <tbody>
                @forelse($metricas['huella_territorial'] as $loc)
                    @php
                        $pesoProvincia = $metricas['kpis']['parcelas_desplegadas'] > 0 ? round(($loc['total'] / $metricas['kpis']['parcelas_desplegadas']) * 100, 2) : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $loc['provincia'] }}</strong></td>
                        <td class="text-center font-bold">{{ number_format($loc['total']) }} parc.</td>
                        <td>
                            <div style="font-size: 8px; font-weight: bold; text-align: right; margin-bottom: 1px;">{{ $pesoProvincia }}%</div>
                            <div class="bar-bg"><div class="bar-fill" style="width: {{ $pesoProvincia }}%;"></div></div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-slate-400">Sin distribución geoespacial activa.</td></tr>
                @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>


<div class="page-break"></div>

<div class="header-box">
    <h1 class="title">Anexo Operativo: Desglose Técnico por Línea POA</h1>
    <p class="subtitle">Trazabilidad de Ensayos Científicos, Estaciones de Control y Distribución Político-Administrativa de Parcelas</p>
</div>

@forelse($poas_detallados as $productoId => $ensayos)
    @php $primerEnsayo = $ensayos->first(); @endphp

    <div class="poa-header">
        PRODUCTO ESTRATÉGICO POA: {{ $primerEnsayo->producto->name ?? 'SIN CLASIFICACIÓN INSTITUCIONAL VINCULADA' }}
    </div>

    <table class="data-table" style="margin-top: 4px;">
        <thead>
        <tr>
            <th style="width: 25%;">Línea de Ensayo</th>
            <th style="width: 20%;">Estación Experimental Matriz</th>
            <th style="width: 20%;">Cuerpo Científico Responsable</th>
            <th style="width: 35%;">Estructura de Parcelas Desplegadas (Localización y Estado)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($ensayos as $ensayo)
            <tr>
                <td>
                    <strong style="color: #0f172a; font-size: 9.5px;">{{ $ensayo->nombre }}</strong><br>
                    <span style="font-size: 7.5px; color: #64748b; text-transform: uppercase;">{{ $ensayo->tipo_tecnologia }}</span>
                </td>
                <td>
                    <span style="font-weight: 600; color: #334155;">{{ $ensayo->location->name ?? 'Sede Central INIAP' }}</span>
                </td>
                <td style="color: #475569; font-size: 8.5px;">
                    {{ $ensayo->equipoTecnico->pluck('name')->join(', ') ?: 'Sin asignación de personal' }}
                </td>
                <td>
                    @if($ensayo->parcelas->count() > 0)
                        <table style="width: 100%; background: transparent; border: none; font-size: 8.5px;">
                            @foreach($ensayo->parcelas as $p)
                                <tr>
                                    <td style="padding: 1px 0; border: none; color: #334155;">
                                        • {{ $p->nombre }}
                                        <span style="color: #64748b; font-size: 7.5px;">
                                            ({{ $p->provincia->name ?? 'N/A' }} / {{ $p->canton->name ?? 'N/A' }}@if($p->localidad) - {{ $p->localidad }} @endif)
                                        </span>
                                    </td>
                                    <td style="padding: 1px 0; text-align: right; border: none; width: 60px;">
                                        <span class="badge
                                            @if($p->estado === 'Implementado') badge-active
                                            @elseif($p->estado === 'Finalizado') badge-harvested
                                            @elseif($p->estado === 'Perdido' || $p->estado === 'Dado de baja') badge-lost
                                            @else badge-queue @endif">
                                            {{ $p->estado }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <span style="color: #94a3b8; font-style: italic; font-size: 8.5px;">No existen parcelas vinculadas a esta línea de investigación.</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@empty
    <p style="text-align: center; color: #64748b; padding-top: 40px;">No se identificaron registros operativos bajo los criterios de consulta actuales.</p>
@endforelse

</body>
</html>

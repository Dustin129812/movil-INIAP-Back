<!DOCTYPE html>
<html lang="es">
<head>
    <title>Informe de Monitoreo Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Base */
        @page { size: A4 landscape; margin: 15mm; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 8px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }

        /* Encabezado */
        .header-container { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header-container td { vertical-align: middle; border: none; padding: 0; }
        .header-logo { width: 15%; text-align: left; }
        .header-logo.right { text-align: right; }
        .header-title { width: 70%; text-align: center; }
        .header-title h1 { font-size: 15px; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-title p { font-size: 10px; color: #64748b; margin: 4px 0 0 0; }
        .header-container img { max-width: 80px; height: auto; }

        /* Tabla de Información */
        .info-table { margin-bottom: 20px; border: 1px solid #cbd5e1; }
        .info-table td { padding: 6px 10px; border: 1px solid #cbd5e1; }
        .info-table .label { font-size: 8px; color: #475569; text-transform: uppercase; font-weight: bold; width: 12%; background-color: #f8fafc; }
        .info-table .value { font-size: 10px; color: #0f172a; font-weight: bold; }

        /* Cuadro de Resumen (KPIs) */
        .summary-wrapper { margin-bottom: 20px; }
        .summary-title { font-size: 10px; font-weight: bold; color: #0f172a; margin-bottom: 8px; text-transform: uppercase; }
        .summary-table { border-collapse: separate; border-spacing: 6px; width: 100%; }
        .summary-table td { border-radius: 6px; padding: 10px; text-align: center; border: 1px solid #e2e8f0; background-color: #f8fafc; }
        .stat-main { background-color: #f0fdf4 !important; border-color: #bbf7d0 !important; }
        .stat-danger { background-color: #fff1f2 !important; border-color: #fecaca !important; }
        .stat-val { display: block; font-size: 18px; font-weight: bold; margin-bottom: 3px; color: #0f172a; }
        .stat-label { font-size: 7px; text-transform: uppercase; font-weight: bold; color: #64748b; }
        .stat-main .stat-val { color: #166534; } .stat-danger .stat-val { color: #991b1b; }

        /* Tabla Principal */
        .activity-table th { background-color: #f8fafc; color: #475569; font-size: 8px; text-transform: uppercase; padding: 8px; border: 1px solid #cbd5e1; text-align: center; }
        .activity-table td { border: 1px solid #cbd5e1; padding: 8px; color: #334155; line-height: 1.4; vertical-align: middle; }
        .activity-table tr:nth-child(even) { background-color: #fafafa; }
        .row-novelty { background-color: #fff1f2 !important; } /* Resaltado sutil para novedades */

        /* Etiquetas (Tags) */
        .badge { font-size: 7px; padding: 3px 6px; border-radius: 4px; font-weight: bold; letter-spacing: 0.5px; display: inline-block; margin-bottom: 4px; text-transform: uppercase; }
        .badge-apoyo { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-novedad { background-color: #ffe4e6; color: #e11d48; border: 1px solid #fda4af; }

        /* Píldoras de Estado */
        .status-pill { padding: 4px 8px; border-radius: 4px; font-size: 8px; font-weight: bold; text-align: center; text-transform: uppercase; display: inline-block; width: 80%; }
        .status-completed { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-partial { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-not-done { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .material-detail { margin: 0; padding: 2px 0; border-bottom: 1px dashed #e2e8f0; }
        .material-detail:last-child { border-bottom: none; }

        .alert-info { background-color: #f8fafc; border-left: 4px solid #3b82f6; color: #475569; padding: 10px 15px; font-size: 9px; margin-top: 20px; }
        .text-center { text-align: center; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body>

<table class="header-container">
    <tr>
        <td class="header-logo"><img src="{{ $iniap_logo_path }}" alt="Logo INIAP"></td>
        <td class="header-title">
            <h1>Informe de Monitoreo Semanal de Actividades</h1>
            <p>Rubro / Departamento: {{ $program_rubro }}</p>
        </td>
        <td class="header-logo right"><img src="{{ $ecuador_shield_path }}" alt="Escudo Ecuador"></td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="label">Técnico:</td><td class="value">{{ $technician->name }}</td>
        <td class="label">Ubicación:</td><td class="value">{{ $technician->location->name ?? 'No especificada' }}</td>
    </tr>
    <tr>
        <td class="label">Semana del:</td><td class="value">{{ $startDate->translatedFormat('d \d\e F \d\e Y') }}</td>
        <td class="label">Al:</td><td class="value">{{ $endDate->translatedFormat('d \d\e F \d\e Y') }}</td>
    </tr>
</table>

<div class="summary-wrapper">
    <div class="summary-title">Resumen Ejecutivo de Ejecución</div>
    <table class="summary-table">
        <tr>
            <td class="stat-main">
                <span class="stat-val">{{ number_format($summary['overall_compliance'], 2) }}%</span>
                <span class="stat-label">Cumplimiento Planificado</span>
            </td>
            <td>
                <span class="stat-val">{{ $summary['completed'] }}</span>
                <span class="stat-label">Planificadas Completadas</span>
            </td>
            <td>
                <span class="stat-val">{{ $summary['partial'] }}</span>
                <span class="stat-label">Planificadas Parciales</span>
            </td>
            <td>
                <span class="stat-val">{{ $summary['not_done'] }}</span>
                <span class="stat-label">No Realizadas</span>
            </td>
            <td class="stat-danger">
                <span class="stat-val">{{ $summary['total_novelties'] ?? 0 }}</span>
                <span class="stat-label">Novedades (Fuera de Plan)</span>
            </td>
        </tr>
    </table>
</div>

<table class="activity-table">
    <thead>
    <tr>
        <th style="width:{{ $widths['date'] }}%;">Fecha</th>
        <th style="width:{{ $widths['activity'] }}%;">Actividad Ejecutada</th>
        @if($visibility['indicators']) <th style="width:{{ $widths['verification'] }}%;">Verificación</th> @endif
        @if($visibility['materials']) <th style="width:{{ $widths['materials'] }}%;">Materiales Usados</th> @endif
        @if($visibility['logistics']) <th style="width:{{ $widths['logistics'] }}%;">Apoyo Logístico</th> @endif
        <th style="width:{{ $widths['status'] }}%;">Estado Final</th>
        <th style="width:{{ $widths['observations'] }}%;">Observaciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($weekActivities as $activity)
        @php
            $statusClass = '';
            $statusText = '';
            switch ($activity->status) {
                case 'completed': $statusClass = 'status-completed'; $statusText = 'Completada'; break;
                case 'partial': $statusClass = 'status-partial'; $statusText = 'Parcial'; break;
                case 'rated':
                case 'not completed': $statusClass = 'status-not-done'; $statusText = 'No Realizada'; break;
                default: $statusText = $activity->status;
            }
        @endphp

        <tr class="{{ $activity->is_novelty ? 'row-novelty' : '' }}">
            <td class="text-center capitalize">
                <strong>{{ \Carbon\Carbon::parse($activity->date)->translatedFormat('l') }}</strong><br>
                {{ \Carbon\Carbon::parse($activity->date)->format('d/m') }}
            </td>
            <td>
                @if($activity->is_novelty) <span class="badge badge-novedad">NOVEDAD</span><br> @endif
                @if(isset($activity->is_owner) && !$activity->is_owner) <span class="badge badge-apoyo">APOYO</span><br> @endif
                {!! nl2br(e($activity->formatted_description ?? '--')) !!}
            </td>
            @if($visibility['indicators'])
                <td>{{ ($activity->is_novelty ? $activity->indicators : $activity->performanceIndicators)->pluck('name')->implode(', ') ?: '--' }}</td>
            @endif
            @if($visibility['materials'])
                <td>
                    @forelse($activity->materials as $material)
                        <div class="material-detail">
                            <strong>{{ $material->name }}</strong><br>
                            <span style="font-size: 7px; color:#64748b;">Cant: {{ $material->pivot->quantity ?? 'N/A' }} | {{ $material->pivot->description ?? 'Sin ref.' }}</span>
                        </div>
                    @empty -- @endforelse
                </td>
            @endif
            @if($visibility['logistics'])
                <td>{{ ($activity->is_novelty ? $activity->logisticSupport : $activity->logisticSupportUsers)->pluck('name')->implode(', ') ?: '--' }}</td>
            @endif
            <td class="text-center">
                <div class="status-pill {{ $statusClass }}">{{ $statusText }}</div>
                @if($activity->is_novelty) <div style="font-size: 7px; margin-top: 4px; color: #e11d48; font-weight: bold;">FUERA DE PLAN</div> @endif
            </td>
            <td>{!! nl2br(e($activity->observations ?? '--')) !!}</td>
        </tr>
    @empty
        <tr>
            @php $colspan = 4 + ($visibility['indicators'] ? 1 : 0) + ($visibility['materials'] ? 1 : 0) + ($visibility['logistics'] ? 1 : 0); @endphp
            <td colspan="{{ $colspan }}" class="text-center" style="padding: 20px; color: #94a3b8;">
                No se encontraron actividades registradas para este periodo.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

@if($omittedColumnsText)
    <div class="alert-info">
        <strong>Optimización de Espacio:</strong> {{ $omittedColumnsText }}
    </div>
@endif

</body>
</html>

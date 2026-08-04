<!DOCTYPE html>
<html lang="es">
<head>
    <title>Planificación Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Base y Tipografía */
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #334155; margin: 10mm; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }

        /* Encabezado Principal */
        .header-container { width: 100%; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header-container td { vertical-align: middle; border: none; padding: 0; }
        .header-logo { width: 15%; text-align: left; }
        .header-logo.right { text-align: right; }
        .header-title { width: 70%; text-align: center; }
        .header-title h1 { font-size: 15px; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .header-title p { font-size: 10px; color: #64748b; margin: 4px 0 0 0; }
        .header-container img { max-width: 80px; height: auto; }

        /* Tabla de Información General */
        .info-table { margin-bottom: 20px; background-color: #f8fafc; border: 1px solid #cbd5e1; }
        .info-table td { padding: 6px 10px; border: 1px solid #cbd5e1; }
        .info-table .label { font-size: 8px; color: #475569; text-transform: uppercase; font-weight: bold; width: 15%; background-color: #f1f5f9; }
        .info-table .value { font-size: 10px; color: #0f172a; font-weight: bold; width: 35%; }

        /* Tabla Principal de Actividades */
        .activity-table th { background-color: #f8fafc; color: #475569; font-size: 8px; text-transform: uppercase; padding: 8px; border: 1px solid #cbd5e1; text-align: center; }
        .activity-table td { border: 1px solid #cbd5e1; padding: 8px; color: #334155; line-height: 1.4; vertical-align: top; }
        .activity-table tr:nth-child(even) { background-color: #fafafa; }
        .row-date { background-color: #f8fafc; font-weight: bold; color: #0f172a; }

        /* Badges y Etiquetas */
        .badge-apoyo {
            background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;
            font-size: 7px; padding: 3px 6px; border-radius: 4px; font-weight: bold;
            letter-spacing: 0.5px; display: inline-block; margin-bottom: 4px; text-transform: uppercase;
        }

        /* Footer de Información */
        .alert-info { background-color: #f8fafc; border-left: 4px solid #3b82f6; color: #475569; padding: 10px 15px; font-size: 9px; margin-top: 20px; }
        .alert-info strong { color: #1e3a8a; }

        /* Utilidades */
        .text-center { text-align: center; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body>

<table class="header-container">
    <tr>
        <td class="header-logo"><img src="{{ $iniap_logo_path }}" alt="Logo INIAP"></td>
        <td class="header-title">
            <h1>Planificación Semanal de Actividades</h1>
            <p>{{ $technician_location }}</p>
        </td>
        <td class="header-logo right"><img src="{{ $ecuador_shield_path }}" alt="Escudo Ecuador"></td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="label">Responsable:</td><td class="value">{{ $technician->name }}</td>
        <td class="label">Área / Rubro:</td><td class="value">{{ $program_rubro }}</td>
    </tr>
    <tr>
        <td class="label">Semana de Trabajo:</td><td class="value" colspan="3">{{ $week_range }}</td>
    </tr>
</table>

<table class="activity-table">
    <thead>
    <tr>
        <th style="width: {{ $widths['date'] }}%;">Fecha</th>
        <th style="width: {{ $widths['product'] }}%;">Producto</th>
        <th style="width: {{ $widths['rubro'] }}%;">Rubro</th>
        <th style="width: {{ $widths['activity'] }}%;">Actividad</th>
        <th style="width: {{ $widths['description'] }}%;">Descripción de Tarea</th>
        @if($visibility['support']) <th style="width: {{ $widths['support'] }}%;">Personal de Apoyo</th> @endif
        @if($visibility['indicators']) <th style="width: {{ $widths['indicator'] }}%;">Indicador</th> @endif
        <th style="width: {{ $widths['observations'] }}%;">Observaciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($weekActivities as $date => $activitiesOnThisDay)
        @foreach ($activitiesOnThisDay as $activity)
            <tr>
                @if ($loop->first)
                    <td class="text-center capitalize row-date" rowspan="{{ count($activitiesOnThisDay) }}">
                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l d/m') }}
                    </td>
                @endif
                <td>{{ $activity->activity->product->name ?? '--' }}</td>
                <td>{{ $activity->activity->product->rubro->name ?? '--' }}</td>
                <td>{{ $activity->activity->description ?? '--' }}</td>
                <td>
                    @if(isset($activity->is_owner) && !$activity->is_owner)
                        <span class="badge-apoyo">APOYO</span><br>
                    @endif
                    {!! nl2br(e($activity->description ?? '--')) !!}
                </td>
                @if($visibility['support']) <td>{{ $activity->logisticSupportUsers->pluck('name')->implode(', ') ?: '--' }}</td> @endif
                @if($visibility['indicators']) <td>{{ $activity->performanceIndicators->pluck('name')->implode(', ') ?: '--' }}</td> @endif
                <td>{{ $activity->observations ?? '--' }}</td>
            </tr>
        @endforeach
    @empty
        <tr>
            @php $colspan = 5 + ($visibility['support'] ? 1 : 0) + ($visibility['indicators'] ? 1 : 0); @endphp
            <td colspan="{{ $colspan }}" class="text-center" style="padding: 20px; color: #94a3b8;">
                No se encontraron actividades planificadas para este rango de fechas.
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

<!DOCTYPE html>
<html>
<head>
    <title>Planificación Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            margin: 10mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #c0c0c0;
            padding: 4px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #e9e9e9;
            font-weight: bold;
            text-align: center;
            color: #333;
        }
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        .header-container {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .header-logo, .header-title {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .alert-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            padding: 8px;
            border-radius: 6px;
            font-size: 9px;
            margin-top: 15px;
            text-align: center;
            page-break-inside: avoid;
        }
        .alert-info strong {
            color: #172554;
            font-weight: bold;
        }
        .info-icon {
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: #3b82f6;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 12px;
            font-size: 8px;
            margin-right: 5px;
            font-weight: bold;
        }
        .header-logo.left { width: 10%; text-align: left; }
        .header-logo.right { width: 10%; text-align: right; }
        .header-title { width: 80%; }
        .header-title h1 { font-size: 14px; margin: 0; }
        .header-title p { font-size: 10px; margin: 2px 0; color: #555; }
        .header-container img { max-width: 60px; height: auto; }
        .info-table { margin-bottom: 15px; }
        .info-table td { border: 1px solid #c0c0c0; padding: 5px 8px; }
        .info-table .label { font-weight: bold; width: 15%; }
        .info-table .value { width: 35%; }
        .activity-table td {
            font-size: 9px;
        }
        .text-center { text-align: center; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body>

{{-- El encabezado y la tabla de información general no cambian --}}
<div class="header-container">
    <div class="header-logo left">
        <img src="{{ $iniap_logo_path }}" alt="INIAP Logo">
    </div>
    <div class="header-title">
        <h1>PLANIFICACIÓN SEMANAL DE ACTIVIDADES</h1>
        <p>{{ $technician_location }}</p>
    </div>
    <div class="header-logo right">
        <img src="{{ $ecuador_shield_path }}" alt="Escudo de Ecuador">
    </div>
</div>

<table class="info-table">
    <tr>
        <td class="label">Responsable:</td>
        <td class="value">{{ $technician->name }}</td>
        <td class="label">Área / Rubro General:</td>
        <td class="value">{{ $program_rubro }}</td>
    </tr>
    <tr>
        <td class="label">Semana de Trabajo:</td>
        <td class="value" colspan="3">{{ $week_range }}</td>
    </tr>
</table>


<table class="activity-table">
    <thead>
    <tr>
        {{-- Usamos los anchos dinámicos calculados en el controlador --}}
        <th style="width: {{ $widths['date'] }}%;">Fecha</th>
        <th style="width: {{ $widths['product'] }}%;">Producto</th>
        <th style="width: {{ $widths['rubro'] }}%;">Rubro</th>
        <th style="width: {{ $widths['activity'] }}%;">Actividad</th>
        <th style="width: {{ $widths['description'] }}%;">Descripción de Tarea</th>

        @if($visibility['support'])
            <th style="width: {{ $widths['support'] }}%;">Personal de Apoyo</th>
        @endif

        @if($visibility['indicators'])
            <th style="width: {{ $widths['indicator'] }}%;">Indicador Asociado</th>
        @endif

        <th style="width: {{ $widths['observations'] }}%;">Observaciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($weekActivities as $date => $activitiesOnThisDay)
        @foreach ($activitiesOnThisDay as $activity)
            <tr>
                @if ($loop->first)
                    <td class="text-center capitalize" rowspan="{{ count($activitiesOnThisDay) }}">
                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l d/m') }}
                    </td>
                @endif

                <td>{{ $activity->activity->product->name ?? '--' }}</td>
                <td>{{ $activity->activity->product->rubro->name ?? '--' }}</td>
                <td>{{ $activity->activity->description ?? '--' }}</td>
                <td>{{ $activity->description ?? '--' }}</td>

                {{-- Solo mostramos la celda si la columna es visible --}}
                @if($visibility['support'])
                    <td>{{ $activity->logisticSupportUsers->pluck('name')->implode(', ') ?: '--' }}</td>
                @endif

                @if($visibility['indicators'])
                    <td>{{ $activity->performanceIndicators->pluck('name')->implode(', ') ?: '--' }}</td>
                @endif

                <td>{{ $activity->observations ?? '--' }}</td>
            </tr>
        @endforeach
    @empty
        <tr>
            {{-- Calculamos el colspan dinámicamente --}}
            @php
                $colspan = 5 + ($visibility['support'] ? 1 : 0) + ($visibility['indicators'] ? 1 : 0);
            @endphp
            <td colspan="{{ $colspan }}" class="text-center" style="padding: 15px;">
                No se encontraron actividades planificadas para este rango de fechas.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- CUADRO AZUL INFORMATIVO AL FINAL --}}
@if($omittedColumnsText)
    <div class="alert-info">
        <span class="info-icon">i</span>
        <strong>Optimización de Espacio:</strong>
        {{ $omittedColumnsText }}
    </div>
@endif

</body>
</html>

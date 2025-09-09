{{-- resources/views/reports/weekly_monitoring_report.blade.php --}}

    <!DOCTYPE html>
<html lang="es">
<head>
    <title>Informe de Monitoreo Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { size: A4 landscape; margin: 15mm; }
        body { font-family: 'Arial', sans-serif; font-size: 8px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px; text-align: left; vertical-align: top; }
        th { background-color: #E0E0E0; font-weight: bold; text-align: center; color: #000; }
        .header-container { width: 100%; margin-bottom: 20px; border: none; }
        .header-container td { border: none; vertical-align: middle; text-align: center; }
        .header-logo { width: 15%; }
        .header-title { width: 70%; }
        .header-container img { max-width: 80px; height: auto; }
        .header-title h1 { font-size: 16px; margin: 0; }
        .header-title p { font-size: 12px; margin: 2px 0; }
        .info-table { margin-bottom: 20px; }
        .info-table td { border: 1px solid #999; padding: 6px 8px; }
        .info-table .label { font-weight: bold; background-color: #F0F0F0; width: 15%; }
        .summary-container { margin-bottom: 20px; }
        .summary-title { font-size: 13px; font-weight: bold; text-align: center; padding: 8px; background-color: #E0E0E0; border: 1px solid #999; border-bottom: none; }
        .summary-table { text-align: center; }
        .summary-table td { padding: 10px; font-size: 11px; }
        .summary-table strong { display: block; font-size: 16px; margin-bottom: 4px; }
        .status { text-align: center; font-weight: bold; border-radius: 4px; padding: 4px; color: white; }
        .status-completed { background-color: #27ae60; }
        .status-partial { background-color: #f39c12; }
        .status-not-done { background-color: #c0392b; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .capitalize { text-transform: capitalize; }
        .material-detail {
            margin: 0;
            padding: 1px 0;
            white-space: normal;
        }
        .material-detail strong {
            color: #000;
        }    </style>
</head>
<body>

{{-- Encabezado (sin cambios) --}}
<table class="header-container">
    <tr>
        <td class="header-logo" style="text-align: left;"><img src="{{ $iniap_logo_path }}" alt="Logo INIAP"></td>
        <td class="header-title">
            <h1>INFORME DE MONITOREO SEMANAL DE ACTIVIDADES</h1>
            <p>(Programa: {{ $program_rubro }})</p>
        </td>
        <td class="header-logo" style="text-align: right;"><img src="{{ $ecuador_shield_path }}" alt="Escudo Ecuador"></td>
    </tr>
</table>

{{-- Información General (sin cambios) --}}
<table class="info-table">
    <tr>
        <td class="label">TÉCNICO:</td><td>{{ $technician->name }}</td>
        <td class="label">UBICACIÓN:</td><td>{{ $technician->location->name ?? 'No especificada' }}</td>
    </tr>
    <tr>
        <td class="label">SEMANA DEL:</td><td>{{ $startDate->translatedFormat('d \d\e F \d\e Y') }}</td>
        <td class="label">AL:</td><td>{{ $endDate->translatedFormat('d \d\e F \d\e Y') }}</td>
    </tr>
</table>

{{-- Resumen de Cumplimiento (sin cambios) --}}
<div class="summary-container">
    <div class="summary-title">Resumen General de Cumplimiento</div>
    <table class="summary-table">
        <tr>
            <td><strong>{{ number_format($summary['overall_compliance'], 2) }}%</strong>Cumplimiento General</td>
            <td><strong>{{ $summary['partial'] }}</strong>Completadas (&lt;100%)</td>
            <td><strong>{{ $summary['not_done'] }}</strong>No Realizadas (0%)</td>
        </tr>
    </table>
</div>

{{-- Tabla Principal de Actividades (MODIFICADA) --}}
<table>
    <thead>
    <tr>
        <th style="width:7%;">Fecha</th>
        <th style="width:28%;">Actividad</th>
        <th style="width:15%;">Verificación</th>
        <th style="width:15%;">Materiales</th>
        <th style="width:15%;">Apoyo Logístico</th>
        <th style="width:5%;">Estado</th>
        <th style="width:10%;">Observaciones</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($weekActivities as $activity)
        @php
            $statusClass = '';
            $statusText = '';
            if ($activity->percentage > 0) {
                $statusClass = 'status-completed'; $statusText = 'Completada';
            } else {
                $statusClass = 'status-not-done'; $statusText = 'No Realizada';
            }
        @endphp
        <tr>
            <td class="text-center capitalize">{{ \Carbon\Carbon::parse($activity->date)->translatedFormat('l d/m') }}</td>

            <td class="font-bold">{{ $activity->formatted_description }}</td>

            <td>{{ $activity->performanceIndicators->pluck('name')->implode(', ') ?: '--' }}</td>

            <td> @if($activity->materials->isNotEmpty())
                    @foreach($activity->materials as $material)
                        <p class="material-detail">
                            {{ $material->name }}
                            ({{ $material->pivot->quantity ?? 'N/A' }}
                            -
                            {{ $material->pivot->description ?? 'N/A' }})
                        </p>
                    @endforeach
                @else
                    --
                @endif
            </td>

            <td>{{ $activity->logisticSupportUsers->pluck('name')->implode(', ') ?: '--' }}</td>

            <td class="text-center"><div class="status {{ $statusClass }}">{{ $statusText }}</div></td>
            <td>{{ $activity->observations ?? '--' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center" style="padding: 15px;">
                No se encontraron actividades calificadas (reportadas) para este rango de fechas.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>

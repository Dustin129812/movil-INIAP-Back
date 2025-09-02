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
        .details-p { margin: 0 0 3px 0; padding: 0; font-size: 8px; }
        .details-p strong { color: #000; }
    </style>
</head>
<body>

{{-- Encabezado --}}
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

{{-- Información General --}}
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

{{-- Resumen de Cumplimiento --}}
<div class="summary-container">
    <div class="summary-title">Resumen General de Cumplimiento</div>
    <table class="summary-table">
        <tr>
            <td><strong>{{ number_format($summary['overall_compliance'], 2) }}%</strong>Cumplimiento General</td>
            <td><strong>{{ $summary['completed'] }}</strong>Completadas (100%)</td>
            <td><strong>{{ $summary['partial'] }}</strong>Parciales (&lt;100%)</td>
            <td><strong>{{ $summary['not_done'] }}</strong>No Realizadas (0%)</td>
        </tr>
    </table>
</div>

{{-- Tabla Principal de Actividades --}}
<table>
    <thead>
    <tr>
        <th style="width:7%;">Fecha</th>
        <th style="width:58%;">Actividad / Detalles</th>
        <th style="width:5%;">% Cump.</th>
        <th style="width:7%;">Estado</th>
        <th style="width:23%;">Observaciones del Cumplimiento</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($weekActivities as $activity)
        @php
            $statusClass = '';
            $statusText = '';
            if ($activity->percentage == 100) {
                $statusClass = 'status-completed'; $statusText = 'Completada';
            } elseif ($activity->percentage > 0) {
                $statusClass = 'status-partial'; $statusText = 'Parcial';
            } else {
                $statusClass = 'status-not-done'; $statusText = 'No Realizada';
            }
        @endphp
        <tr>
            <td class="text-center capitalize">{{ \Carbon\Carbon::parse($activity->date)->translatedFormat('l d/m') }}</td>
            <td>
                <p class="details-p font-bold">{{ $activity->formatted_description }}</p>
                <p class="details-p"><strong>Responsables:</strong> {{ $activity->activity->users->pluck('name')->implode(', ') ?: '--' }}</p>
                @if($activity->performanceIndicators->isNotEmpty())
                    <p class="details-p"><strong>Verificación:</strong> {{ $activity->performanceIndicators->pluck('name')->implode(', ') }}</p>
                @endif
                @if($activity->materials->isNotEmpty())
                    <p class="details-p"><strong>Materiales:</strong> {{ $activity->materials->pluck('name')->implode(', ') }}</p>
                @endif
                @if($activity->logisticSupportUsers->isNotEmpty())
                    <p class="details-p"><strong>Apoyo Logístico:</strong> {{ $activity->logisticSupportUsers->pluck('name')->implode(', ') }}</p>
                @endif
            </td>
            <td class="text-center font-bold">{{ $activity->percentage }}%</td>
            <td class="text-center"><div class="status {{ $statusClass }}">{{ $statusText }}</div></td>
            <td>{{ $activity->observations ?? '--' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center" style="padding: 15px;">
                No se encontraron actividades calificadas (reportadas) para este rango de fechas.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>

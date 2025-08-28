<!DOCTYPE html>
<html>
<head>
    <title>Informe de Bienestar y Carga de Trabajo</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Estilos similares a tu reporte existente para mantener consistencia */
        body { font-family: 'Arial', sans-serif; font-size: 10px; margin: 15mm; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #c0c0c0; padding: 5px 8px; text-align: left; vertical-align: top; }
        th { background-color: #e9e9e9; font-weight: bold; text-align: center; }
        .header-container { width: 100%; margin-bottom: 20px; text-align: center; }
        .header-logo { width: 80px; }
        .header-title { font-size: 16px; font-weight: bold; margin: 0; }
        .header-subtitle { font-size: 12px; margin: 5px 0; }

        /* Estilos para el semáforo */
        .status-cell { text-align: center; font-weight: bold; }
        .status-green { color: #166534; background-color: #dcfce7; }
        .status-yellow { color: #854d0e; background-color: #fef9c3; }
        .status-red { color: #991b1b; background-color: #fee2e2; }
        .status-gray { color: #4b5563; background-color: #f3f4f6; }

        /* Estilos para la sección de resumen */
        .summary-container { margin-bottom: 20px; padding: 10px; border: 1px solid #e0e0e0; background-color: #f9f9f9; border-radius: 5px; }
        .summary-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; text-align: center; }
        .summary-bar { display: inline-block; height: 15px; }
    </style>
</head>
<body>
{{-- Encabezado del Documento --}}
<div class="header-container">
    <img src="{{ $iniap_logo_path }}" alt="INIAP Logo" class="header-logo">
    <h1 class="header-title">Informe de Bienestar y Carga de Trabajo</h1>
    <p class="header-subtitle">Equipo: {{ $teamName }}</p>
    <p class="header-subtitle">Semana del {{ $startDate->format('d/m/Y') }} al {{ $endDate->format('d/m/Y') }}</p>
</div>

{{-- Resumen del Semáforo --}}
<div class="summary-container">
    <h2 class="summary-title">Resumen del Pulso Semanal ({{ $summary['total'] }} Miembros)</h2>
    <div style="width: 100%; background-color: #e0e0e0; border-radius: 3px;">
        <div class="summary-bar status-green" style="width: {{ $summary['percentages']['green'] }}%;"></div>
        <div class="summary-bar status-yellow" style="width: {{ $summary['percentages']['yellow'] }}%;"></div>
        <div class="summary-bar status-red" style="width: {{ $summary['percentages']['red'] }}%;"></div>
        <div class="summary-bar status-gray" style="width: {{ $summary['percentages']['gray'] }}%;"></div>
    </div>
    <table style="margin-top: 10px; font-size: 9px;">
        <tr>
            <td class="status-green"><strong>Manejable:</strong> {{ $summary['counts']['green'] }} ({{ $summary['percentages']['green'] }}%)</td>
            <td class="status-yellow"><strong>Ocupado:</strong> {{ $summary['counts']['yellow'] }} ({{ $summary['percentages']['yellow'] }}%)</td>
            <td class="status-red"><strong>Sobrecargado:</strong> {{ $summary['counts']['red'] }} ({{ $summary['percentages']['red'] }}%)</td>
            <td class="status-gray"><strong>Sin Reporte:</strong> {{ $summary['counts']['gray'] }} ({{ $summary['percentages']['gray'] }}%)</td>
        </tr>
    </table>
</div>

{{-- Tabla de Detalles --}}
<table>
    <thead>
    <tr>
        <th style="width: 25%;">Investigador</th>
        <th style="width: 15%;">Pulso Semanal</th>
        <th>Comentarios del Investigador</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($teamPulseData as $pulse)
        <tr>
            <td>{{ $pulse['name'] }}</td>
            @php
                $statusClass = 'status-' . $pulse['status'];
                $statusText = [
                    'green' => 'Manejable',
                    'yellow' => 'Ocupado',
                    'red' => 'Sobrecargado',
                    'gray' => 'Sin Reporte'
                ][$pulse['status']];
            @endphp
            <td class="status-cell {{ $statusClass }}">{{ $statusText }}</td>
            <td>{{ $pulse['comment'] ?? '--' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3" style="text-align: center;">No se encontraron reportes de pulso para esta semana.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>

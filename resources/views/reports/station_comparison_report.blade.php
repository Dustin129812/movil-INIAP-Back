    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Análisis Comparativo de Estaciones</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #333; }
        @page { margin: 20mm; }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #1e3a8a; }
        .header p { font-size: 12px; margin: 5px 0; }
        .footer { position: fixed; bottom: -20mm; left: 0; right: 0; text-align: center; font-size: 9px; color: #888; }
        .section { margin-top: 20px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #1e3a8a; }
        .summary-text { background-color: #eef2ff; border-left: 4px solid #4f46e5; padding: 10px; margin-bottom: 20px; font-size: 10px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: center; }
        th { background-color: #eef2ff; font-weight: bold; }
        .text-left { text-align: left; }
        .rank-bar-container { background-color: #e0e0e0; width: 100%; border-radius: 3px; }
        .rank-bar { height: 12px; background-color: #4f46e5; border-radius: 3px; }
        .pulse-ok { color: #16a34a; }
        .pulse-warn { color: #f59e0b; }
        .pulse-danger { color: #dc2626; }
    </style>
</head>
<body>
<div class="footer">Informe generado por SIMPAGI el {{ date('d/m/Y') }}</div>
<div class="header">
    <h1>Análisis Comparativo de Rendimiento</h1>
    <p>Estaciones Experimentales a Nivel Nacional</p>
</div>

<div class="section">
    <h3 class="section-title">Resumen Ejecutivo y Puntos Clave</h3>
    <div class="summary-text">
        @if($topPerformer)
            <p><strong>Estación Destacada:</strong> {{ $topPerformer['location_name'] }} lidera el ranking con un <strong>{{ $topPerformer['poa_progress'] }}%</strong> de cumplimiento del POA.</p>
        @endif
        @if($lowPerformer)
            <p><strong>Área de Oportunidad:</strong> {{ $lowPerformer['location_name'] }} presenta el área de oportunidad más significativa con un <strong>{{ $lowPerformer['poa_progress'] }}%</strong> de cumplimiento.</p>
        @endif
        @if($pulseAlert)
            <p><strong>Alerta de Bienestar:</strong> El equipo de <strong>{{ $pulseAlert['location_name'] }}</strong> reporta el pulso promedio más bajo (<strong>{{ number_format($pulseAlert['average_pulse_score'], 2) }}/3.0</strong>), lo que podría indicar una posible sobrecarga de trabajo.</p>
        @endif
    </div>
</div>

<div class="section">
    <h3 class="section-title">Ranking de Cumplimiento POA</h3>
    <table>
        @foreach($performanceData as $station)
            <tr>
                <td class="text-left" style="width: 25%;"><strong>{{ $station['location_name'] }}</strong></td>
                <td style="width: 75%;">
                    <div class="rank-bar-container">
                        <div class="rank-bar" style="width: {{ $station['poa_progress'] }}%;"></div>
                    </div>
                </td>
                <td style="width: 10%; font-weight: bold;">{{ $station['poa_progress'] }}%</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="section">
    <h3 class="section-title">Tabla Comparativa Detallada</h3>
    <table>
        <thead>
        <tr>
            <th>Estación</th>
            <th>Progreso POA</th>
            <th>Nº Proyectos</th>
            <th>Nº Investigadores</th>
            <th>Presupuesto Asignado</th>
            <th>Pulso Promedio (de 3)</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($performanceData as $station)
            @php
                $pulseClass = 'pulse-ok';
                if ($station['average_pulse_score'] < 2.5) $pulseClass = 'pulse-warn';
                if ($station['average_pulse_score'] < 1.8) $pulseClass = 'pulse-danger';
            @endphp
            <tr>
                <td class="text-left">{{ $station['location_name'] }}</td>
                <td><strong>{{ $station['poa_progress'] }}%</strong></td>
                <td>{{ $station['project_count'] }}</td>
                <td>{{ $station['researcher_count'] }}</td>
                <td>${{ number_format($station['total_budget'], 0, ',', '.') }}</td>
                <td class="{{ $pulseClass }}">{{ number_format($station['average_pulse_score'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>

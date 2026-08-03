<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de Rendimiento - {{ $user->name }}</title>
    <style>
        /* Estilos generales para el documento */
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
        }

        @page {
            margin: 20mm;
        }

        h1, h2, h3 {
            font-family: 'Helvetica Neue', sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f7f7f7;
            font-weight: bold;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }

        .section {
            margin-top: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1e3a8a;
        }

        /* Azul oscuro */
        .intro-text {
            font-size: 12px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .product-list {
            list-style-type: none;
            padding-left: 0;
        }

        .product-list > li {
            margin-bottom: 10px;
        }

        .activity-list {
            list-style-type: disc;
            padding-left: 20px;
            color: #555;
        }

        .pulse-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        .pulse-status-green {
            color: #166534;
            font-weight: bold;
        }

        .pulse-status-yellow {
            color: #c2410c;
            font-weight: bold;
        }

        .pulse-status-red {
            color: #b91c1c;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -20mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #888;
        }

        .log-date-header {
            font-size: 10px;
            font-weight: bold;
            background-color: #f7f7f7;
            padding: 5px;
            margin-top: 10px;
        }

        .log-item {
            border-bottom: 1px solid #eee;
            padding: 5px 0;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-completed {
            color: #15803d;
            background-color: #dcfce7;
        }

        .status-partial {
            color: #b45309;
            background-color: #fef3c7;
        }

        .status-not-completed {
            color: #b91c1c;
            background-color: #fee2e2;
        }
    </style>
</head>
<body>
<div class="footer">Informe generado por SIMPAGI el {{ $reportDate }}</div>
<div class="header">
    <h1>Informe Detallado de Rendimiento</h1>
    <h2>{{ $user->name }}</h2>
    <p>Periodo de Análisis: <strong>{{ $startDate }}</strong> al <strong>{{ $endDate }}</strong></p>
</div>


<div class="section">
    <h3 class="section-title">Resumen del Periodo</h3>
    <p class="intro-text">
        Durante el periodo analizado, {{ $user->name }} reportó un total de
        <strong>{{ $performanceStats['total_activities'] }}</strong> actividades, alcanzando un porcentaje de
        cumplimiento promedio del <strong>{{ number_format($performanceStats['average_compliance'], 1) }}%</strong>. A
        continuación, se detalla el desglose de dichas actividades.
    </p>
    <table>
        <thead>
        <tr>
            <th>Actividades Totales</th>
            <th style="color: green;">Completadas</th>
            <th style="color: orange;">Parciales</th>
            <th style="color: red;">No Completadas</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="text-align: center;">{{ $performanceStats['total_activities'] }}</td>
            <td style="text-align: center;">{{ $performanceStats['completed'] }}</td>
            <td style="text-align: center;">{{ $performanceStats['partial'] }}</td>
            <td style="text-align: center;">{{ $performanceStats['not_completed'] }}</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="section">
    <h3 class="section-title">Bitácora de Trabajo Realizado</h3>
    <p class="intro-text">
        A continuación se presenta un registro cronológico de todas las tareas semanales reportadas por el colaborador durante el periodo de análisis.
    </p>

    @forelse($groupedActivities as $date => $activities)
        <div class="log-date-header">{{ ucfirst($date) }}</div>
        @foreach($activities as $activity)
            @php
                $style = '';
                if ($activity->status == 'completed') $style = 'status-completed';
                elseif ($activity->status == 'partial') $style = 'status-partial';
                elseif ($activity->status == 'not completed' || $activity->status == 'rated') $style = 'status-not-completed';
            @endphp
            <div class="log-item">
                <span class="status-badge {{ $style }}">{{ $activity->status }}</span>
                <span>{{ $activity->description }}</span>
            </div>
        @endforeach
    @empty
        <p><em>No se registraron actividades finalizadas durante este periodo.</em></p>
    @endforelse
</div>

<div class="section">
    <h3 class="section-title">Distribución de Carga Semanal</h3>
    <p class="intro-text">
        La siguiente tabla muestra cómo se distribuyeron las actividades finalizadas a lo largo de las semanas,
        permitiendo identificar patrones de carga de trabajo y productividad.
    </p>
    <table>
        <thead>
        <tr>
            <th>Semana</th>
            <th>Completadas</th>
            <th>Parciales</th>
            <th>No Completadas</th>
        </tr>
        </thead>
        <tbody>
        @forelse($weeklyLoadChart as $week)
            <tr>
                <td>{{ $week['week'] }}</td>
                <td style="text-align: center;">{{ $week['completed'] }}</td>
                <td style="text-align: center;">{{ $week['partial'] }}</td>
                <td style="text-align: center;">{{ $week['not_completed'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align: center;"><em>Sin datos de carga semanal.</em></td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="section">
    <h3 class="section-title">Pulso y Colaboración del Equipo</h3>
    <p class="intro-text">
        Esta sección ofrece una visión cualitativa del bienestar reportado por el colaborador y sus interacciones de
        apoyo con otros miembros del equipo.
    </p>

    <strong>Historial de Pulso Semanal:</strong>
    @forelse($pulseHistory as $pulse)
        <div class="pulse-item">
            <strong>Semana del {{ Carbon\Carbon::parse($pulse->week_start_date)->format('d/m/Y') }}:</strong>
            <span class="pulse-status-{{$pulse->status}}">{{ ucfirst($pulse->status) }}</span><br>
            <em>Comentario: {{ $pulse->comment ?: 'Sin comentario.' }}</em>
        </div>
    @empty
        <p><em>Sin reportes de pulso en este periodo.</em></p>
    @endforelse

    <br>

    <strong>Análisis de Colaboración:</strong>
    <table>
        <thead>
        <tr>
            <th>Apoyo Solicitado a:</th>
            <th>Apoyo Brindado a:</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <ul>
                    @forelse($collaborationStats['support_requested'] as $name => $count)
                        <li>{{ $name }} ({{ $count }} {{ $count > 1 ? 'veces' : 'vez' }})</li>
                    @empty
                        <li><em>Ninguno</em></li>
                    @endforelse
                </ul>
            </td>
            <td>
                <ul>
                    @forelse($collaborationStats['support_given'] as $name => $count)
                        <li>{{ $name }} ({{ $count }} {{ $count > 1 ? 'veces' : 'vez' }})</li>
                    @empty
                        <li><em>Ninguno</em></li>
                    @endforelse
                </ul>
            </td>
        </tr>
        </tbody>
    </table>
</div>

</body>
</html>

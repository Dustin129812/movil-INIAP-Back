    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de Situación Nacional</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #333; }
        @page { margin: 18mm; }
        .header h1 { font-size: 20px; color: #1e3a8a; }
        .footer { position: fixed; bottom: -18mm; left: 0; right: 0; text-align: center; font-size: 8px; color: #888; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 15px; margin-bottom: 8px; color: #1e3a8a; border-bottom: 1px solid #a5b4fc; padding-bottom: 3px;}

        .station-card {
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 12px;
            background-color: #f9fafb;
            overflow: hidden;
        }
        .station-header {
            background-color: #eef2ff;
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .station-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .station-body {
            padding: 10px 12px;
            display: block;
        }
        .station-main-metrics {
            display: inline-block;
            width: 70%;
            vertical-align: top;
        }
        .station-progress-highlight {
            display: inline-block;
            width: 28%;
            text-align: right;
            vertical-align: top;
        }
        .station-progress-highlight .value {
            font-size: 32px;
            font-weight: bold;
            color: #1e3a8a;
            line-height: 1;
        }
        .station-progress-highlight .label {
            font-size: 9px;
            color: #6b7280;
        }
        .kpi-item { font-size: 10px; margin-bottom: 4px; }
        .kpi-item strong { color: #000; }

        .station-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 8px 12px;
        }
        .researcher-list {
            font-size: 8px;
            color: #6b7280;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
<div class="footer">Informe de Situación Nacional | Generado por SIMPAGI el {{ date('d/m/Y') }}</div>
<div class="header" style="text-align: center;"><h1>Informe de Situación Nacional</h1></div>

<div class="section-title">Análisis Detallado por Estación Experimental</div>
<p style="font-size: 10px; margin-bottom: 10px;">
    Análisis de los indicadores clave para cada estación, ordenadas por su nivel de cumplimiento del POA. Se evalúa el presupuesto, la carga de proyectos y el equipo de investigación asignado.
</p>

@foreach($stationData as $station)
    <div class="station-card">
        <div class="station-header">
            <div class="station-title">{{ $station['name'] }}</div>
        </div>
        <div class="station-body">
            <div class="station-main-metrics">
                <div class="kpi-item"><strong>Presupuesto Asignado:</strong> ${{ number_format($station['total_budget'], 0, ',', '.') }}</div>
                <div class="kpi-item"><strong>Proyectos Totales / Activos:</strong> {{ $station['project_count'] }} / <strong>{{ $station['active_projects_count'] }}</strong></div>
                <div class="kpi-item"><strong>Ritmo de Avance Mensual (Est.):</strong> {{ $station['monthly_progress_estimate'] }}%</div>
            </div>
            <div class="station-progress-highlight">
                <div class="value">{{ $station['poa_progress'] }}%</div>
                <div class="label">Progreso POA</div>
            </div>
        </div>
        <div class="station-footer">
            <strong style="font-size: 9px;">Equipo de Investigación ({{ $station['researcher_count'] }}):</strong>
            <p class="researcher-list">{{ !empty($station['researchers']) ? implode(', ', $station['researchers']) : 'Sin investigadores asignados.' }}</p>
        </div>
    </div>
@endforeach

</body>
</html>

    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha Detallada - {{ $rubro['name'] }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        @page { margin: 20mm; }
        h1, h2, h3 { font-family: 'Helvetica Neue', sans-serif; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f0f7ff; font-weight: bold; }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 22px; }
        .section { margin-top: 20px; page-break-inside: avoid; }
        .section-title { font-size: 15px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #a5b4fc; padding-bottom: 4px;}
        .kpi-table { margin-bottom: 15px; }
        .kpi-table td { text-align: center; font-size: 14px; font-weight: bold; }
        .kpi-table .label { font-size: 10px; font-weight: normal; color: #555; }
        .product-block { margin-bottom: 15px; }
        .product-title { font-size: 12px; font-weight: bold; background-color: #eef2ff; padding: 6px; }
        .activity-item { margin-left: 15px; padding-left: 10px; border-left: 2px solid #c7d2fe; padding-bottom: 8px; }
        .weekly-activity { font-size: 9px; color: #4b5563; }
        .footer { position: fixed; bottom: -20mm; left: 0; right: 0; text-align: center; font-size: 9px; color: #888; }
    </style>
</head>
<body>
<div class="footer">Informe generado por SIMPAGI el {{ date('d/m/Y') }}</div>
<div class="header">
    <h1>Ficha Técnica Detallada de Rubro</h1>
    <h2>{{ $rubro['name'] }}</h2>
</div>

<div class="section">
    <h3 class="section-title">Resumen General</h3>
    <table class="kpi-table">
        <tr>
            <td>${{ number_format($rubro['total_budget'] ?? 0, 2) }}<br><span class="label">Presupuesto</span></td>
            <td>{{ count($rubro['products']) }}<br><span class="label">Productos</span></td>
            <td>{{ count($rubro['groups']) }}<br><span class="label">Equipos</span></td>
        </tr>
    </table>
    <strong>Equipos de Trabajo:</strong>
    @forelse($rubro['groups'] as $group)
        {{ $group['name'] }}{{ !$loop->last ? ', ' : '' }}
    @empty
        No hay equipos asignados.
    @endforelse
</div>

<div class="section">
    <h3 class="section-title">Detalle de Productos y Actividades</h3>
    @forelse ($rubro['products'] as $product)
        <div class="product-block">
            <div class="product-title">{{ $product['name'] }}</div>
            @forelse ($product['activities'] as $activity)
                <div class="activity-item">
                    <p><strong>Actividad:</strong> {{ $activity['description'] }}</p>
                    @if(!empty($activity['weekly_activities']))
                        <table>
                            <thead>
                            <tr>
                                <th style="width: 15%;">Fecha</th>
                                <th style="width: 20%;">Responsable</th>
                                <th>Descripción de Tarea Semanal</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($activity['weekly_activities'] as $wa)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($wa['date'])->format('d/m/Y') }}</td>
                                    <td>{{ $wa['user']['name'] ?? 'N/A' }}</td>
                                    <td class="weekly-activity">{{ $wa['description'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="weekly-activity"><em>Sin tareas semanales reportadas para esta actividad.</em></p>
                    @endif
                </div>
            @empty
                <p style="margin-left: 15px;"><em>Este producto no tiene actividades registradas.</em></p>
            @endforelse
        </div>
    @empty
        <p>No se encontraron productos para este rubro.</p>
    @endforelse
</div>
</body>
</html>

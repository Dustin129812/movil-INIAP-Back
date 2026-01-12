<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Pago - {{ $report->id }}</title>
    {{-- (Usamos los mismos estilos del otro PDF) --}}
    <style>
        @page { margin: 25px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header { width: 100%; text-align: center; position: relative; }
        .header h2 { font-size: 14px; margin: 0; }
        .header h3 { font-size: 12px; margin: 5px 0; font-weight: normal; }
        .header .subtitle { font-size: 11px; font-weight: bold; margin-top: 10px; }
        .section-title { background-color: #E0E0E0; font-weight: bold; padding: 4px; margin-top: 10px; margin-bottom: 5px; text-align: center; border: 1px solid #999; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #999; padding: 4px; text-align: left; }
        th { background-color: #F0F0F0; font-size: 9px; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .w-25 { width: 25%; }
        /* ... (Copia todos los estilos de overtime_report.blade.php) ... */
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h2>INSTITUTO NACIONAL DE INVESTIGACIONES AGROPECUARIAS</h2>
        <h3>DIRECCIÓN ADMINISTRATIVA FINANCIERA</h3>
        {{-- --- TÍTULO CAMBIADO --- --}}
        <div class="subtitle">REPORTE DE PAGO DE HORAS SUPLEMENTARIAS Y/O EXTRAORDINARIAS</div>
    </div>

    <div class="section-title">1. DATOS DEL TRABAJADOR</div>
    {{-- (Esta sección es idéntica a overtime_report.blade.php) --}}
    <table>
        <tbody>
        <tr>
            <td class="font-bold w-25">NOMBRE:</td>
            <td>{{ $report->driver->name ?? 'N/A' }}</td>
            <td class="font-bold w-25">PUESTO:</td>
            <td>{{ $report->driver->position->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="font-bold">UNIDAD / PROCESO:</td>
            <td>{{ $report->driver->location->name ?? 'N/A' }}</td>
            <td class="font-bold">SUELDO/SALARIO MENSUAL:</td>
            <td>${{ number_format($report->rmu_at_submission, 2) }}</td>
        </tr>
        </tbody>
    </table>

    <div class="section-title">2. RESUMEN DE PAGO (Aprobado por DAF)</div>
    {{-- (Esta sección es idéntica) --}}
    @php
        $total_s_net_hours = floor($report->total_supplemental_minutes / 60);
        $total_s_net_minutes = $report->total_supplemental_minutes % 60;
        $total_e_net_hours = floor($report->total_extraordinary_minutes / 60);
        $total_e_net_minutes = $report->total_extraordinary_minutes % 60;
    @endphp
    <table>
        <thead>
        <tr>
            <th>TIPO DE HORA</th>
            <th>TOTAL HORAS (HH:MM)</th>
            <th>TOTAL USD</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>SUPLEMENTARIAS (1.5x)</td>
            <td class="text-center">{{ sprintf('%02d:%02d', $total_s_net_hours, $total_s_net_minutes) }}</td>
            <td class="text-right">${{ number_format($report->total_supplemental_usd, 2) }}</td>
        </tr>
        <tr>
            <td>EXTRAORDINARIAS (2.0x)</td>
            <td class="text-center">{{ sprintf('%02d:%02d', $total_e_net_hours, $total_e_net_minutes) }}</td>
            <td class="text-right">${{ number_format($report->total_extraordinary_usd, 2) }}</td>
        </tr>
        <tr style="font-weight: bold; background-color: #F0F0F0;">
            <td>TOTAL USD A PAGAR</td>
            <td class="text-center">--</td>
            <td class="text-right">${{ number_format($report->total_usd_pay, 2) }}</td>
        </tr>
        </tbody>
    </table>

    <div class="section-title">3. DETALLE DE REGISTROS APROBADOS</div>
    {{-- (Esta sección es idéntica) --}}
    <table>
        <thead>
        <tr>
            <th>FECHA</th>
            <th>ACTIVIDAD REALIZADA</th>
            <th>HORA SALIDA</th>
            <th>HORA LLEGADA</th>
            <th>H. SUPLE.</th>
            <th>H. EXTRA.</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($report->entries->sortBy('date') as $entry)
            <tr>
                <td class="text-center">{{ \Carbon\Carbon::parse($entry->date)->format('Y-m-d') }}</td>
                <td>
                    {{ $entry->activityType->name ?? 'N/A' }}
                    @if($entry->observations)
                        <span style="font-style: italic;">- {{ $entry->observations }}</span>
                    @endif
                </td>
                <td class="text-center">{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</td>
                <td class="text-center">{{ \Carbon\CarbonInterval::minutes($entry->supplemental_minutes)->cascade()->format('%H:%I') }}</td>
                <td class="text-center">{{ \Carbon\CarbonInterval::minutes($entry->extraordinary_minutes)->cascade()->format('%H:%I') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center">No hay registros.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- --- SECCIÓN DE FIRMAS ELIMINADA --- --}}
    {{-- --}}

    <div style="margin-top: 40px; text-align: center; font-size: 11px;">
        <p>Reporte generado automáticamente por SIMPAGI.</p>
        <p>Aprobado por DAF: {{ $report->dafApprover->name ?? 'N/A' }} el {{ \Carbon\Carbon::parse($report->daf_approved_at)->format('Y-m-d H:i') }}</p>
        <p>Estado: **PAGO REALIZADO**</p>
    </div>

</div>
</body>
</html>

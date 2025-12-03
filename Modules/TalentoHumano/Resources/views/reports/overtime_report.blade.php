<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Solicitud Horas Extras - {{ $report->id }}</title>
    <style>
        @page {
            margin: 25px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header, .footer {
            width: 100%;
            text-align: center;
            position: relative;
        }
        .header img {
            position: absolute;
            top: 0;
            left: 0;
            width: 150px;
        }
        .header h2 {
            font-size: 14px;
            margin: 0;
        }
        .header h3 {
            font-size: 12px;
            margin: 5px 0;
            font-weight: normal;
        }
        .header .subtitle {
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
        }
        .section-title {
            background-color: #E0E0E0;
            font-weight: bold;
            padding: 4px;
            margin-top: 10px;
            margin-bottom: 5px;
            text-align: center;
            border: 1px solid #999;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #999;
            padding: 4px;
            text-align: left;
        }
        th {
            background-color: #F0F0F0;
            font-size: 9px;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .w-25 { width: 25%; }
        .w-50 { width: 50%; }
        .signature-box {
            width: 100%;
            margin-top: 40px;
        }
        .signature-table {
            border: none;
        }
        .signature-table td {
            border: none;
            width: 25%;
            text-align: center;
            padding-top: 50px;
            font-size: 9px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h2>INSTITUTO NACIONAL DE INVESTIGACIONES AGROPECUARIAS</h2>
        <h3>DIRECCIÓN ADMINISTRATIVA FINANCIERA</h3>
        <div class="subtitle">SOLICITUD DE AUTORIZACIÓN Y REPORTE PAGO DE HORAS SUPLEMENTARIAS Y/O EXTRAORDINARIAS</div>
    </div>

    <div class="section-title">1. DATOS DEL TRABAJADOR</div>
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

    <div class="section-title">2. RESUMEN DE HORAS (Calculado por el sistema - Neto a Pagar)</div>

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
            <td>{{ number_format($report->total_supplemental_minutes / 60, 2) }}</td>
            <td>${{ number_format($report->total_supplemental_usd, 2) }}</td>
        </tr>

        <tr>
            <td>EXTRAORDINARIAS (2.0x)</td>
            <td>{{ number_format($report->total_extraordinary_minutes / 60, 2) }}</td>
            <td>${{ number_format($report->total_extraordinary_usd, 2) }}</td>
        </tr>

        <tr style="font-weight: bold; background-color: #F0F0F0;">
            <td>TOTAL USD A PAGAR</td>
            <td class="text-center">--</td>
            <td class="text-right">${{ number_format($report->total_usd_pay, 2) }}</td>
        </tr>

        </tbody>
    </table>

    <div class="section-title">3. LIQUIDACIÓN DE HORAS (Registros del conductor - Brutos)</div>
    <table>
        <thead>
        <tr>
            <th>FECHA</th>
            <th>JUSTIFICACIÓN DE ACTIVIDAD</th>
            <th>HORA DE INICIO</th>
            <th>HORA DE FINALIZACIÓN</th>
            <th>H. SUPLE.</th>
            <th>H. EXTRA.</th>
        </tr>
        </thead>
        <tbody>

        @php
            $sortedEntries = $report->entries->sortBy('date');
        @endphp

        @forelse ($sortedEntries as $entry)
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
            <tr>
                <td colspan="6" class="text-center">No hay registros para este reporte.</td>
            </tr>
        @endforelse

        @if($report->entries->isNotEmpty())
            @php
                $total_s_bruto_min = $report->entries->sum('supplemental_minutes');
                $total_s_bruto_hours = floor($total_s_bruto_min / 60);
                $total_s_bruto_minutes = $total_s_bruto_min % 60;

                $total_e_bruto_min = $report->entries->sum('extraordinary_minutes');
                $total_e_bruto_hours = floor($total_e_bruto_min / 60);
                $total_e_bruto_minutes = $total_e_bruto_min % 60;

                $total_s_diff_min = $total_s_bruto_min - $report->total_supplemental_minutes;
                $total_s_diff_hours = floor($total_s_diff_min / 60);
                $total_s_diff_minutes = $total_s_diff_min % 60;

                $total_e_diff_min = $total_e_bruto_min - $report->total_extraordinary_minutes;
                $total_e_diff_hours = floor($total_e_diff_min / 60);
                $total_e_diff_minutes = $total_e_diff_min % 60;
            @endphp
            <tr style="background-color: #F9F9F9; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL NRO HORAS:</td>
                <td class="text-center">
                    {{ sprintf('%02d:%02d', $total_s_bruto_hours, $total_s_bruto_minutes) }}
                </td>
                <td class="text-center">
                    {{ sprintf('%02d:%02d', $total_e_bruto_hours, $total_e_bruto_minutes) }}
                </td>
            </tr>
            <tr style="background-color: #F0F0F0; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL NRO MAXIMO PERMITIDO:</td>
                <td class="text-center">
                    {{ sprintf('%02d:%02d', $total_s_net_hours, $total_s_net_minutes) }}
                </td>
                <td class="text-center">
                    {{ sprintf('%02d:%02d', $total_e_net_hours, $total_e_net_minutes) }}
                </td>
            </tr>
        @endif

        </tbody>
    </table>

    <div class="signature-box">
        <table class="signature-table">
            <tbody>
            <tr>
                <td>
                    <div class="signature-line"></div>
                    SOLICITADO POR<br>
                    {{ $report->driver->name ?? 'N/A' }}<br>
                    CONDUCTOR
                </td>
                <td>
                    <div class="signature-line"></div>
                    APROBADO POR<br>
                    {{ $mobilityAuthority->name ?? 'PENDIENTE DE ASIGNACIÓN' }}<br>
                    RESPONSABLE DE MOVILIDAD
                </td>
                <td>
                    <div class="signature-line"></div>
                    APROBADO POR<br>
                    {{ $dafAuthority->name ?? 'PENDIENTE DE ASIGNACIÓN' }}<br>
                    DIRECTOR ADMINISTRATIVO FINANCIERO
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

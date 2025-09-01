<!DOCTYPE html>
<html lang="es">
<head>
    <title>Planificación Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            margin: 15mm 15mm;
            size: A4 landscape;
        }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.4;
            color: #2c3e50;
        }

        .header, .footer {
            width: 100%;
            position: fixed;
            left: 0;
            right: 0;
        }

        .header {
            top: -12mm;
            height: 100px;
        }

        .footer {
            bottom: -10mm;
            height: 50px;
            font-size: 10pt;
            text-align: center;
            color: #7f8c8d;
        }

        .pagenum:before {
            content: "Página " counter(page);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #bdc3c7;
            padding: 10px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #ecf0f1;
            font-weight: bold;
            text-align: center;
            font-size: 13pt;
        }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-4 { margin-bottom: 1.5rem; }

        .info-table {
            margin-bottom: 25px;
        }
        .info-table td {
            border: none;
            padding: 4px 0;
            font-size: 12pt;
        }

        .activities-table tbody tr {
            height: 29mm;
        }
        .activities-table .col-fecha { width: 10%; }
        .activities-table .col-actividad { width: 28%; }
        .activities-table .col-lugar { width: 12%; }
        .activities-table .col-apoyo { width: 20%; }
        .activities-table .col-observaciones { width: 30%; }
    </style>
</head>
<body>

<div class="header">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 20%; text-align: left; border: none;">
                <img src="{{ $iniap_logo_path }}" alt="INIAP Logo" style="width: 150px;">
            </td>
            <td style="width: 60%; text-align: center; border: none; vertical-align: middle;">
                <h2 style="margin: 0; font-size: 18px; font-weight: bold;">PLANIFICACIÓN SEMANAL DE ACTIVIDADES</h2>
                <p style="margin: 5px 0 0; font-size: 16px;">Subdirección General de Investigación y Desarrollo</p>
            </td>
            <td style="width: 20%; text-align: right; border: none;">
                <img src="{{ $ecuador_shield_path }}" alt="Escudo Ecuador" style="width: 90px;">
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <p class="pagenum"></p>
</div>

<main>
    <table class="info-table mb-4">
        <tr>
            <td class="font-bold" style="width: 10%;">TÉCNICO:</td>
            <td style="width: 40%;">{{ $technician->name }}</td>
            <td class="font-bold" style="width: 25%;">ESTACIÓN / GRANJA / SEDE:</td>
            <td style="width: 25%;">{{ $location }}</td>
        </tr>
        <tr>
            <td class="font-bold">SEMANA DEL:</td>
            <td>{{ $startDate }}</td>
            <td class="font-bold">AL:</td>
            <td>{{ $endDate }}</td>
        </tr>
    </table>

    <table class="activities-table">
        <thead>
        <tr>
            <th class="col-fecha">FECHA Y DÍA</th>
            <th class="col-actividad">ACTIVIDADES A CUMPLIR</th>
            <th class="col-lugar">LUGAR</th>
            <th class="col-apoyo">APOYO LOGÍSTICO REQUERIDO</th>
            <th class="col-observaciones">OBSERVACIONES</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($daysOfWeek as $day)
            @php
                $currentDate = Carbon::parse($startDate)->startOfWeek()->addDays(array_search($day, ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']));
                $currentDayActivities = $weekActivities->where('date', $currentDate->format('Y-m-d'));
            @endphp
            <tr>
                <td class="text-center font-bold">{{ $day }}<br>{{ $currentDate->format('d/m/Y') }}</td>
                <td>
                    @forelse ($currentDayActivities as $activity)
                        <p>{{ $activity->formatted_description }}</p>
                    @empty
                        <p class="text-center">-</p>
                    @endforelse
                </td>
                <td>
                    @forelse ($currentDayActivities as $activity)
                        <p>{{ $activity->place }}</p>
                    @empty
                        <p class="text-center">-</p>
                    @endforelse
                </td>
                <td>
                    @forelse ($currentDayActivities as $activity)
                        @if ($activity->logisticSupportUsers->isNotEmpty())
                            <ul style="margin: 0; padding-left: 15px;">
                                @foreach ($activity->logisticSupportUsers as $support)
                                    <li>{{ $support->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-center">-</p>
                        @endif
                    @empty
                        <p class="text-center">-</p>
                    @endforelse
                </td>
                <td>
                    @forelse ($currentDayActivities as $activity)
                        <p>{{ $activity->observations }}</p>
                    @empty
                        <p class="text-center">-</p>
                    @endforelse
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</main>

</body>
</html>

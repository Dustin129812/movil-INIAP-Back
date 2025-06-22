<!DOCTYPE html>
<html>
<head>
    <title>Planificación Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20mm; /* Márgenes para impresión */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            max-width: 80px; /* Ajusta según el tamaño de tus logos */
            height: auto;
            margin: 0 10px;
            vertical-align: middle;
        }
        .header h1 {
            font-size: 16px;
            margin: 5px 0;
        }
        .header p {
            font-size: 12px;
            margin: 2px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            border: none;
            padding: 3px 0;
        }
        .activity-details {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #ccc; /* Separador entre actividades del mismo día */
        }
        .activity-details:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        ul {
            margin: 0;
            padding-left: 15px;
        }
        li {
            margin-bottom: 2px;
        }
        .observations {
            padding: 5px;
            margin-top: 5px;
            font-size: 9px;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .capitalize { text-transform: capitalize; }
    </style>
</head>
<body>

<div class="header">
    {{-- <img src="{{ $iniap_logo_path }}" alt="INIAP Logo"> --}}
    {{-- <img src="{{ $ecuador_shield_path }}" alt="Escudo de Ecuador"> --}}
    <h1>{{ $technician_location }}</h1>
    <p>PLANIFICACIÓN SEMANAL DE ACTIVIDADES – (Programa: {{ $program_rubro }})</p>
</div>

<table class="info-table">
    <tr>
        <td class="font-bold" style="width: 25%;">Nombre del Técnico:</td>
        <td>{{ $technician->name }}</td>
        <td class="font-bold" style="width: 25%;">Fecha de presentación:</td>
        <td>{{ $presentation_date }}</td>
    </tr>
    <tr>
        <td class="font-bold">SEMANA DE TRABAJO:</td>
        <td colspan="3">{{ $week_range }}</td>
    </tr>
</table>

<table>
    <thead>
    <tr>
        <th style="width: 10%;">FECHA</th>
        <th style="width: 25%;">Actividades Planificadas</th>
        <th style="width: 15%;">Medio de verificación</th>
        <th style="width: 20%;">Materiales e insumos requeridos</th>
        <th style="width: 20%;">Apoyo logístico o técnico requerido</th>
        <th style="width: 10%;">Observaciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($days_of_week as $dayName)
        @php
            $dayIndex = array_search($dayName, ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sábado', 'domingo']);
            $currentDate = $start_date_obj->copy()->addDays($dayIndex);
            $currentDayActivities = $weekActivities->get($currentDate->format('Y-m-d'), collect());
        @endphp
        <tr>
            <td class="text-center capitalize">
                {{ $dayName }}
                <br>
                {{ $currentDate->format('d \d\e F') }}
            </td>

            {{-- ACTIVIDADES --}}
            <td>
                @forelse ($currentDayActivities as $activity)
                    <div class="activity-details">
                        <p>{{ $activity->activity->description ?? 'N/A' }}</p>
                    </div>
                @empty
                    <p>No hay actividades planificadas.</p>
                @endforelse
            </td>

            {{-- MEDIOS DE VERIFICACIÓN --}}
            <td>
                @forelse ($currentDayActivities as $activity)
                    <div class="activity-details">
                        @if (!empty($activity->performanceIndicators) && is_iterable($activity->performanceIndicators) && $activity->performanceIndicators->isNotEmpty())
                            <ul>
                                @foreach ($activity->performanceIndicators as $indicator)
                                    <li>{{ $indicator->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>N/A</p>
                        @endif
                    </div>
                @empty
                    <p>N/A</p>
                @endforelse
            </td>

            {{-- MATERIALES --}}
            <td>
                @forelse ($currentDayActivities as $activity)
                    <div class="activity-details">
                        @if (!empty($activity->materials) && is_iterable($activity->materials) && $activity->materials->isNotEmpty())
                            <ul>
                                @foreach ($activity->materials as $material)
                                    <li>
                                        {{ $material->name }}
                                        @if(isset($material->pivot->quantity))
                                            (Cant: {{ $material->pivot->quantity }})
                                        @endif
                                        @if(isset($material->pivot->description))
                                            - {{ $material->pivot->description }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>N/A</p>
                        @endif
                    </div>
                @empty
                    <p>N/A</p>
                @endforelse
            </td>

            {{-- APOYO LOGÍSTICO --}}
            <td>
                @forelse ($currentDayActivities as $activity)
                    <div class="activity-details">
                        @if (!empty($activity->logisticSupports) && is_iterable($activity->logisticSupports) && $activity->logisticSupports->isNotEmpty())
                            <ul>
                                @foreach ($activity->logisticSupports as $support)
                                    <li>{{ $support->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>N/A</p>
                        @endif
                    </div>
                @empty
                    <p>N/A</p>
                @endforelse
            </td>

            {{-- OBSERVACIONES --}}
            <td>
                @forelse ($currentDayActivities as $activity)
                    <div class="activity-details">
                        @if (!empty($activity->observations))
                            <div class="observations">
                                {{ $activity->observations }}
                            </div>
                        @else
                            <p>N/A</p>
                        @endif
                    </div>
                @empty
                    <p>N/A</p>
                @endforelse
            </td>
        </tr>
    @endforeach
    </tbody>

</table>

</body>
</html>

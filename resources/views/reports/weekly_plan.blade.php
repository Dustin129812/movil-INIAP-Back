<!DOCTYPE html>
<html>
<head>
    <title>Planificación Semanal de Actividades</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* ESTILOS GENERALES Y DE IMPRESIÓN */
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            margin: 10mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #c0c0c0;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #e9e9e9;
            font-weight: bold;
            text-align: center;
            color: #333;
            white-space: nowrap;
        }

        /* Orientación de la página (¡ESENCIAL PARA LANDSCAPE!) */
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        /* ESTILOS DEL ENCABEZADO (HEADER) */
        .header-container {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .header-logo, .header-title {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        .header-logo.left {
            width: 10%;
            text-align: left;
        }
        .header-logo.right {
            width: 10%;
            text-align: right;
        }
        .header-title {
            width: 80%;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .header-title h1 {
            font-size: 14px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        .header-title p {
            font-size: 10px;
            margin: 2px 0; /* Ajuste pequeño para la p */
            padding: 0;
            line-height: 1.2;
            color: #555;
        }
        .header-container img {
            max-width: 60px;
            height: auto;
            display: block;
            /* AÑADIDO: Margen para separar las imágenes */
            margin: 0 10px; /* 0 arriba/abajo, 10px a los lados */
        }
        /* Ajustes específicos para los flotantes si se mantienen */
        .header-logo.left img { float: left; margin-right: 15px; margin-left: 0; } /* Más espacio a la derecha del logo izquierdo */
        .header-logo.right img { float: right; margin-left: 15px; margin-right: 0; } /* Más espacio a la izquierda del logo derecho */


        /* ESTILOS DE LA TABLA DE INFORMACIÓN PRINCIPAL */
        .info-table {
            border: 1px solid #c0c0c0;
            margin-bottom: 15px;
        }
        .info-table td {
            border: none;
            padding: 5px 8px;
        }
        .info-table tr:first-child td {
            padding-top: 8px;
        }
        .info-table tr:last-child td {
            padding-bottom: 8px;
        }
        .info-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 20%;
            border-right: 1px solid #c0c0c0;
        }
        .info-table .value {
            width: 30%;
        }
        .info-table .full-width {
            width: 80%;
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }

        /* ESTILOS DE LA TABLA DE ACTIVIDADES */
        .activity-table th {
            font-size: 9px;
        }
        .activity-table td {
            padding: 3px 5px;
            font-size: 9px;
        }
        .activity-details {
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #e0e0e0;
        }
        .activity-details:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        ul {
            margin: 0;
            padding-left: 10px;
            list-style-type: disc;
        }
        li {
            margin-bottom: 1px;
        }
        .observations {
            font-size: 8px;
            color: #555;
        }

        /* CLASES DE UTILIDAD */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .capitalize { text-transform: capitalize; }
        .no-border { border: none !important; }
        .vertical-line {
            border-left: 1px solid #c0c0c0;
            height: 100%;
            display: inline-block;
            vertical-align: middle;
            margin: 0 15px;
        }
    </style>
</head>
<body>

{{-- NUEVO ENCABEZADO CON ALINEACIÓN MEJORADA --}}
<div class="header-container">
    <div class="header-logo left">
        <img src="{{ $iniap_logo_path }}" alt="INIAP Logo">
    </div>
    <div class="header-title">
        <h1>{{ $technician_location }}</h1> {{-- Mover la ubicación aquí si es deseado --}}
        <p>PLANIFICACIÓN SEMANAL DE ACTIVIDADES – (Programa: {{ $program_rubro }})</p>
    </div>
    <div class="header-logo right">
        <img src="{{ $ecuador_shield_path }}" alt="Escudo de Ecuador">
    </div>
</div>

{{-- TABLA DE INFORMACIÓN DEL TÉCNICO Y PERIODO --}}
<table class="info-table">
    <tr>
        <td class="label">Nombre del Técnico:</td>
        <td class="value">{{ $technician->name }}</td>
        <td class="label">Fecha de presentación:</td>
        <td class="value">{{ $presentation_date }}</td>
    </tr>
    <tr>
        <td class="label">SEMANA DE TRABAJO:</td>
        <td colspan="3" class="full-width">{{ $week_range }}</td>
    </tr>
</table>

{{-- TABLA PRINCIPAL DE ACTIVIDADES --}}
<table class="activity-table">
    <thead>
    <tr>
        <th style="width: 8%;">FECHA</th>
        <th style="width: 28%;">Actividades Planificadas</th>
        <th style="width: 16%;">Medio de verificación</th>
        <th style="width: 16%;">Materiales e insumos requeridos</th>
        <th style="width: 16%;">Apoyo logístico o técnico requerido</th>
        <th style="width: 16%;">Observaciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($days_of_week as $dayName)
        @php
            $dayIndex = array_search($dayName, ['lunes', 'martes', 'miercoles', 'jueves', 'viernes']); // Asegúrate de que solo se incluyan los días que necesitas
            $currentDate = $start_date_obj->copy()->addDays($dayIndex);
            $currentDayActivities = $weekActivities->get($currentDate->format('Y-m-d'), collect());
        @endphp
        <tr>
            <td class="text-center capitalize">
                <span class="font-bold">{{ $dayName }}</span>
                <br>
                {{ $currentDate->format('d \d\e F') }}
            </td>

            {{-- ACTIVIDADES --}}
            <td>
                @forelse ($currentDayActivities as $weekActivity)
                    <div class="activity-details">
                        {{ $weekActivity->formatted_description ?? ($weekActivity->description ?? '') }} {{-- Usa la propiedad calculada --}}
                    </div>
                @empty
                    <p class="text-center">--</p> {{-- Marcador visual para "No hay actividades" --}}
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
                            <p class="text-center">--</p>
                        @endif
                    </div>
                @empty
                    <p class="text-center">--</p>
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
                            <p class="text-center">--</p>
                        @endif
                    </div>
                @empty
                    <p class="text-center">--</p>
                @endforelse
            </td>

            {{-- APOYO LOGÍSTICO --}}
            <td>
                @forelse ($currentDayActivities as $activity)
                    <div class="activity-details">
                        @if (!empty($activity->logisticSupportUsers) && is_iterable($activity->logisticSupportUsers) && $activity->logisticSupportUsers->isNotEmpty())
                            <ul>
                                @foreach ($activity->logisticSupportUsers as $support)
                                    <li>{{ $support->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-center">--</p>
                        @endif
                    </div>
                @empty
                    <p class="text-center">--</p>
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
                            <p class="text-center">--</p>
                        @endif
                    </div>
                @empty
                    <p class="text-center">--</p>
                @endforelse
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>

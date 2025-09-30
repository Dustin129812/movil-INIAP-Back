<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen de Encuesta</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #004a99;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #004a99;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
            color: #555;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }
        .kpi-card {
            width: 33.33%;
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px 0;
        }
        .kpi-card h3 {
            margin: 0;
            font-size: 12px;
            color: #555;
            text-transform: uppercase;
        }
        .kpi-card p {
            margin: 5px 0 0;
            font-size: 22px;
            font-weight: bold;
            color: #004a99;
        }
        .question-block {
            margin-bottom: 25px;
            page-break-inside: avoid; /* Evita que el bloque se parta entre páginas */
        }
        .question-block h4 {
            font-size: 14px;
            background-color: #f2f2f2;
            padding: 8px;
            border-left: 4px solid #004a99;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .percentage-bar-container {
            width: 100%;
            background-color: #f1f1f1;
            border-radius: 2px;
            height: 16px;
        }
        .percentage-bar {
            background-color: #2E86C1;
            height: 16px;
            line-height: 16px;
            color: white;
            text-align: center;
            font-size: 9px;
            border-radius: 2px;
        }
        .text-answer {
            border-left: 3px solid #ccc;
            padding-left: 10px;
            margin-bottom: 5px;
            font-style: italic;
            color: #555;
        }
        .footer {
            position: fixed;
            bottom: -1.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="footer">
    Generado el {{ date('d/m/Y H:i') }} | Reporte de Encuesta - SIMPAGI
</div>

<div class="header">
    {{-- Aquí podrías poner el logo de INIAP --}}
    {{-- <img src="{{ public_path('images/iniap-logo.png') }}" alt="Logo" style="width: 150px; margin-bottom: 10px;"> --}}
    <h1>{{ $data['survey_details']['title'] }}</h1>
    <h2>{{ $data['survey_details']['description'] }}</h2>
</div>

<table class="kpi-container">
    <tr>
        <td class="kpi-card">
            <h3>Respuestas Totales</h3>
            <p>{{ $data['kpis']['total_responses'] }}</p>
        </td>
        <td class="kpi-card">
            <h3>Nº de Preguntas</h3>
            <p>{{ $data['kpis']['total_questions'] }}</p>
        </td>
        <td class="kpi-card">
            <h3>Periodo del Reporte</h3>
            <p style="font-size: 14px;">
                {{ $data['kpis']['first_response_at'] ? date('d/m/Y', strtotime($data['kpis']['first_response_at'])) : 'N/A' }} -
                {{ $data['kpis']['last_response_at'] ? date('d/m/Y', strtotime($data['kpis']['last_response_at'])) : 'N/A' }}
            </p>
        </td>
    </tr>
</table>

@foreach($data['questions'] as $question)
    @php
        // Calculamos el total de respuestas para esta pregunta para los porcentajes
        $totalAnswersOnQuestion = is_array($question['results']) ? array_sum($question['results']) : count($question['results']);
    @endphp
    <div class="question-block">
        <h4>{{ $loop->iteration }}. {{ $question['text'] }}</h4>

        @if(in_array($question['type'], ['radio', 'checkbox', 'boolean', 'scale']) && $totalAnswersOnQuestion > 0)
            <table>
                <thead>
                <tr>
                    <th style="width: 40%;">Opción</th>
                    <th style="width: 10%;">Votos</th>
                    <th style="width: 50%;">Distribución</th>
                </tr>
                </thead>
                <tbody>
                @foreach($question['results'] as $option => $count)
                    @php
                        $percentage = ($count / $totalAnswersOnQuestion) * 100;
                    @endphp
                    <tr>
                        <td>{{ $question['type'] === 'boolean' ? ($option == 1 ? 'Sí' : 'No') : $option }}</td>
                        <td>{{ $count }}</td>
                        <td>
                            <div class="percentage-bar-container">
                                <div class="percentage-bar" style="width: {{ $percentage }}%;">
                                    {{ number_format($percentage, 1) }}%
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @elseif(in_array($question['type'], ['text', 'textarea']))
            <p><strong>Primeras 5 respuestas recibidas:</strong></p>
            @forelse (array_slice($question['results'], 0, 5) as $answer)
                <p class="text-answer">"{{ $answer }}"</p>
            @empty
                <p>No se han recibido respuestas de texto.</p>
            @endforelse
            @if (count($question['results']) > 5)
                <p style="font-size: 9px; color: #777;">(Mostrando 5 de {{ count($question['results']) }} respuestas. Ver exportación a Excel para el detalle completo)</p>
            @endif
        @else
            <p>No se han recibido respuestas para esta pregunta.</p>
        @endif
    </div>
@endforeach
</body>
</html>

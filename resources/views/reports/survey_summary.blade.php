<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen de Encuesta</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

        @page {
            margin: 2cm 1.5cm;
            size: A4 portrait;
        }
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 11pt;
            color: #333333;
            line-height: 1.5;
            background-color: #ffffff;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30pt;
            padding-bottom: 15pt;
            border-bottom: 2px solid #007bff;
        }
        .header h1 {
            margin: 0;
            font-size: 24pt;
            font-weight: 700;
            color: #007bff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            margin: 8pt 0 0;
            font-size: 14pt;
            font-weight: 400;
            color: #6c757d;
        }
        .header p {
            margin: 10pt 0 0;
            font-size: 11pt;
            color: #495057;
            max-width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .kpi-description {
            margin-bottom: 20pt;
            font-size: 11pt;
            color: #495057;
            text-align: center;
            background-color: #f8f9fa;
            padding: 10pt;
            border-radius: 4px;
        }
        .kpi-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30pt;
            gap: 15pt;
        }
        .kpi-card {
            flex: 1;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15pt;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .kpi-card h3 {
            margin: 0 0 8pt;
            font-size: 12pt;
            font-weight: 500;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-card p {
            margin: 0;
            font-size: 22pt;
            font-weight: 700;
            color: #007bff;
        }
        .kpi-card small {
            display: block;
            margin-top: 5pt;
            font-size: 10pt;
            color: #6c757d;
        }
        .question-block {
            margin-bottom: 30pt;
            page-break-inside: avoid;
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15pt;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 15pt;
        }
        .question-number {
            background-color: #007bff;
            color: #ffffff;
            font-size: 14pt;
            font-weight: 700;
            padding: 8pt 12pt;
            border-radius: 50%;
            margin-right: 15pt;
            min-width: 30pt;
            text-align: center;
        }
        .question-text {
            font-size: 16pt;
            font-weight: 500;
            color: #343a40;
        }
        .question-type {
            font-size: 10pt;
            color: #6c757d;
            text-transform: uppercase;
            margin-left: auto;
            background-color: #e9ecef;
            padding: 4pt 8pt;
            border-radius: 4px;
        }
        .question-description {
            font-size: 11pt;
            color: #495057;
            margin-bottom: 15pt;
            padding: 10pt;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        table.results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10pt;
        }
        .results-table th, .results-table td {
            padding: 10pt;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .results-table th {
            background-color: #e9ecef;
            font-weight: 500;
            color: #495057;
            font-size: 11pt;
            text-transform: uppercase;
        }
        .results-table td {
            font-size: 11pt;
        }
        .percentage-bar-container {
            background-color: #e9ecef;
            border-radius: 4px;
            height: 20pt;
            overflow: hidden;
        }
        .percentage-bar {
            background-color: #007bff;
            height: 100%;
            color: #ffffff;
            text-align: right;
            padding-right: 8pt;
            font-size: 10pt;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        .text-answers {
            margin-top: 15pt;
        }
        .text-answer {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 10pt 15pt;
            margin-bottom: 10pt;
            border-radius: 4px;
            font-style: italic;
            color: #495057;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .no-responses {
            font-size: 11pt;
            color: #6c757d;
            text-align: center;
            padding: 15pt;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .footer {
            position: running(footer);
            bottom: -1cm;
            left: 0;
            right: 0;
            height: 1cm;
            text-align: center;
            font-size: 9pt;
            color: #adb5bd;
            border-top: 1px solid #dee2e6;
            padding-top: 5pt;
        }
        @page :first {
            @bottom-center {
                content: element(footer);
            }
        }
        @page {
            @bottom-center {
                content: element(footer);
            }
        }
    </style>
</head>
<body>
<div class="footer">
    Generado el {{ date('d/m/Y H:i') }} | Reporte de Encuesta - SIMPAGI | Página <pdf:pagenumber>
</div>

<div class="container">
    <div class="header">
        {{-- Placeholder para logo --}}
        {{-- <img src="{{ public_path('images/iniap-logo.png') }}" alt="Logo" style="width: 120pt; margin-bottom: 15pt;"> --}}
        <h1>{{ $data['survey_details']['title'] }}</h1>
        <h2>{{ $data['survey_details']['description'] }}</h2>
        <p>
            Este informe presenta los resultados de la encuesta "{{ $data['survey_details']['title'] }}". Incluye métricas clave sobre la participación y un análisis detallado de las respuestas recibidas para cada pregunta, organizadas según su tipo y formato.
        </p>
    </div>

    <div class="kpi-description">
        Las siguientes métricas proporcionan un resumen general de la encuesta, incluyendo el número total de participantes, la cantidad de preguntas incluidas y el período durante el cual se recolectaron las respuestas.
    </div>
    <div class="kpi-section">
        <div class="kpi-card">
            <h3>Respuestas Totales</h3>
            <p>{{ $data['kpis']['total_responses'] }}</p>
            <small>Número de participantes que completaron la encuesta.</small>
        </div>
        <div class="kpi-card">
            <h3>Número de Preguntas</h3>
            <p>{{ $data['kpis']['total_questions'] }}</p>
            <small>Cantidad total de preguntas en la encuesta.</small>
        </div>
        <div class="kpi-card">
            <h3>Período del Reporte</h3>
            <p>
                {{ $data['kpis']['first_response_at'] ? date('d/m/Y', strtotime($data['kpis']['first_response_at'])) : 'N/A' }} –
                {{ $data['kpis']['last_response_at'] ? date('d/m/Y', strtotime($data['kpis']['last_response_at'])) : 'N/A' }}
            </p>
            <small>Rango de fechas en que se recibieron respuestas.</small>
        </div>
    </div>

    @foreach($data['questions'] as $question)
        <div class="question-block">
            <div class="question-header">
                <div class="question-number">{{ $loop->iteration }}</div>
                <div class="question-text">{{ $question['text'] }}</div>
                <div class="question-type">{{ $question['type'] }}</div>
            </div>
            <div class="question-description">
                @php
                    $totalAnswers = $question['type'] === 'text' || $question['type'] === 'textarea'
                        ? count($question['results'])
                        : array_sum($question['results']);
                @endphp
                @if(in_array($question['type'], ['radio', 'boolean', 'scale']))
                    Esta pregunta de opción múltiple recibió {{ $totalAnswers }} respuesta{{ $totalAnswers !== 1 ? 's' : '' }}. Los resultados muestran la distribución de las opciones seleccionadas por los participantes.
                @elseif($question['type'] === 'checkbox')
                    Esta pregunta de selección múltiple permitió elegir varias opciones, recibiendo {{ $totalAnswers }} respuesta{{ $totalAnswers !== 1 ? 's' : '' }} en total. Los resultados reflejan la frecuencia de cada opción seleccionada.
                @elseif(in_array($question['type'], ['text', 'textarea']))
                    Esta pregunta de texto abierto recibió {{ $totalAnswers }} respuesta{{ $totalAnswers !== 1 ? 's' : '' }}. A continuación, se presentan hasta 10 respuestas representativas.
                @else
                    Esta pregunta recibió {{ $totalAnswers }} respuesta{{ $totalAnswers !== 1 ? 's' : '' }}. Los resultados se presentan según el formato de la pregunta.
                @endif
            </div>

            @if(in_array($question['type'], ['radio', 'checkbox', 'boolean', 'scale']) && !empty($question['results']))
                @php
                    $results = $question['results'];
                    $totalAnswersOnQuestion = array_sum($results);
                @endphp

                @if ($totalAnswersOnQuestion > 0)
                    <table class="results-table">
                        <thead>
                        <tr>
                            <th style="width: 40%;">Opción</th>
                            <th style="width: 15%;">Votos</th>
                            <th style="width: 45%;">Porcentaje</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($results as $option => $count)
                            <tr>
                                <td>{{ $option }}</td>
                                <td>{{ $count }}</td>
                                <td>
                                    <div class="percentage-bar-container">
                                        <div class="percentage-bar" style="width: {{ ($count / $totalAnswersOnQuestion) * 100 }}%;">
                                            {{ number_format(($count / $totalAnswersOnQuestion) * 100, 1) }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-responses">
                        No se recibieron respuestas válidas para esta pregunta durante el período seleccionado.
                    </div>
                @endif

            @elseif(in_array($question['type'], ['text', 'textarea']))
                @php
                    $results = $question['results'];
                    $totalAnswersOnQuestion = count($results);
                @endphp
                <div class="text-answers">
                    <p><strong>Respuestas Recibidas (Primeras 10):</strong></p>
                    @forelse (array_slice($results, 0, 10) as $answer)
                        <div class="text-answer">"{{ $answer }}"</div>
                    @empty
                        <div class="no-responses">
                            No se recibieron respuestas de texto para esta pregunta.
                        </div>
                    @endforelse
                    @if ($totalAnswersOnQuestion > 10)
                        <p style="font-size: 10pt; color: #6c757d; margin-top: 10pt;">
                            (Mostrando 10 de {{ $totalAnswersOnQuestion }} respuestas. Exporta a Excel para ver todas.)
                        </p>
                    @endif
                </div>
            @else
                <div class="no-responses">
                    No se recibieron respuestas válidas para esta pregunta.
                </div>
            @endif
        </div>
    @endforeach
</div>
</body>
</html>

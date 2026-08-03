<?php

namespace Modules\Investigacion\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Investigacion\Entities\Survey;
use Spatie\SimpleExcel\SimpleExcelWriter;

class SurveyReportService
{
    public function exportPdf(Survey $survey, array $resultsData)
    {
        // $resultsData debe ser inyectado por el controlador usando el SurveyService base
        return Pdf::loadView('reports.survey_summary', ['data' => $resultsData])
            ->setPaper('a4', 'landscape')
            ->download('resumen-' . Str::slug($survey->title) . '.pdf');
    }

    public function exportExcel(Survey $survey)
    {
        $fileName = 'respuestas-detalladas-' . Str::slug($survey->title) . '.xlsx';

        $results = DB::table('responses')
            ->join('answers', 'responses.id', '=', 'answers.response_id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->leftJoin('users', 'responses.user_id', '=', 'users.id')
            ->where('responses.survey_id', $survey->id)
            ->select('responses.id as response_id', 'responses.created_at as date', 'users.name as user_name', 'users.email as user_email', 'questions.text as question_text', 'questions.type as question_type', 'answers.value as answer_value')
            ->orderBy('responses.id')->cursor();

        return response()->streamDownload(function () use ($results) {
            $writer = SimpleExcelWriter::streamDownload('php://output', 'xlsx');
            $writer->addHeader(['ID Participante (Anónimo)', 'Fecha', 'Nombre Participante', 'Email Participante', 'Pregunta', 'Respuesta']);

            $userMap = [];
            $participantCounter = 1;

            foreach ($results as $row) {
                if (!isset($userMap[$row->user_email])) {
                    $userMap[$row->user_email] = 'Participante ' . $participantCounter++;
                }

                $participantId = $userMap[$row->user_email];
                $formattedValue = $row->answer_value;

                if ($row->question_type == 'checkbox') {
                    $formattedValue = implode(', ', json_decode($row->answer_value) ?? []);
                } elseif ($row->question_type == 'boolean') {
                    $formattedValue = $row->answer_value == 1 ? 'Sí' : 'No';
                }

                $writer->addRow([$participantId, $row->date, $row->user_name, $row->user_email, $row->question_text, $formattedValue]);
            }
        }, $fileName);
    }
}

<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Investigacion\Entities\Answer;
use Modules\Investigacion\Entities\Response;

class ResponseController extends Controller
{
    // Guardar respuestas de un usuario
    public function store(Request $request, $surveyId)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.value' => 'nullable',
        ]);

        $response = Response::create([
            'survey_id' => $surveyId,
            'user_id' => $request->user()?->id,
        ]);

        foreach ($request->answers as $ans) {
            Answer::create([
                'response_id' => $response->id,
                'question_id' => $ans['question_id'],
                'value' => is_array($ans['value'])
                    ? json_encode($ans['value'])
                    : $ans['value'],
            ]);
        }

        return response()->json(['message' => 'Respuesta registrada con éxito'], 201);
    }
}

<?php

namespace App\Modules\Planificacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('pending')) {
            $userId = $request->user()?->id;

            if (!$userId) {

            }

            $respondedSurveyIds = \App\Modules\Planificacion\Models\Response::query()
                ->where('user_id', $userId)
                ->pluck('survey_id');

            $pendingSurvey = Survey::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->whereNotIn('id', $respondedSurveyIds)
                ->with('questions.options')
                ->first();

            return response()->json($pendingSurvey);
        }

        return Survey::where('is_active', true)
            ->with('questions.options')
            ->get();
    }

    // Ver encuesta con preguntas
    public function show(Survey $survey)
    {
        return $survey->load('questions.options');
    }

    // Crear encuesta (admin)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'questions' => 'array',
            'questions.*.text' => 'required|string',
            'questions.*.type' => 'required|string',
            'questions.*.is_required' => 'boolean',
            'questions.*.order' => 'integer',
            'questions.*.options' => 'array',
        ]);

        $survey = Survey::create($validated);

        if ($request->has('questions')) {
            foreach ($request->questions as $q) {
                $question = $survey->questions()->create([
                    'text' => $q['text'],
                    'type' => $q['type'],
                    'is_required' => $q['is_required'] ?? false,
                    'order' => $q['order'] ?? 0,
                ]);

                if (!empty($q['options'])) {
                    foreach ($q['options'] as $opt) {
                        $question->options()->create([
                            'text' => $opt,
                        ]);
                    }
                }
            }
        }

        return response()->json($survey->load('questions.options'), 201);
    }

    // Actualizar encuesta (admin)
    public function update(Request $request, Survey $survey)
    {
        $survey->update($request->only([
            'title', 'description', 'type', 'start_date', 'end_date', 'is_active'
        ]));

        return response()->json($survey);
    }

    // Eliminar encuesta (admin)
    public function destroy(Survey $survey)
    {
        $survey->delete();
        return response()->json(null, 204);
    }

    public function results(Request $request, Survey $survey)
    {
        // Validamos que las fechas, si vienen, tengan el formato correcto.
        $validated = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);

        $responsesQuery = $survey->responses()->has('answers');

        if (!empty($validated['start_date'])) {
            $responsesQuery->whereDate('created_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $responsesQuery->whereDate('created_at', '<=', $validated['end_date']);
        }
        if (!empty($validated['gender'])) {
            $responsesQuery->whereHas('user', function ($q) use ($validated) {
                $q->where('gender', $validated['gender']);
            });
        }
        if (!empty($validated['location_id'])) {
            $responsesQuery->whereHas('user', function ($q) use ($validated) {
                $q->where('location_id', $validated['location_id']);
            });
        }
        if (!empty($validated['question_id']) && !empty($validated['response_value'])) {
            $responsesQuery->whereHas('answers', function ($q) use ($validated) {
                $q->where('question_id', $validated['question_id'])
                    ->where('value', $validated['response_value']);
            });
        }

        $filteredResponseIds = $responsesQuery->pluck('id');

        // --- CÁLCULO DE MÉTRICAS CLAVE (KPIs) BASADO EN FILTROS ---
        $totalResponses = $filteredResponseIds->count();
        $queryForDates = $survey->responses()->whereIn('id', $filteredResponseIds);
        $firstResponseDate = $queryForDates->clone()->min('created_at');
        $lastResponseDate = $queryForDates->clone()->max('created_at');
        $totalQuestions = $survey->questions()->count();

        // --- PROCESAMIENTO AVANZADO DE PREGUNTAS BASADO EN FILTROS ---
        $questionResults = $survey->questions->map(function ($question) use ($filteredResponseIds) {
            // Obtenemos solo las respuestas que pasaron el filtro de fecha.
            $answers = DB::table('answers')
                ->where('question_id', $question->id)
                ->whereIn('response_id', $filteredResponseIds)
                ->pluck('value');

            $data = null;
            $totalAnswersOnQuestion = $answers->count();

            // Cargar las opciones de la pregunta para mapear IDs a textos
            $options = $question->options->pluck('text', 'id')->toArray();

            switch ($question->type) {
                case 'radio':
                case 'scale':
                    // Mapear IDs a textos de opciones, o mantener el valor si no es un ID
                    $data = $answers->countBy()->mapWithKeys(function ($count, $value) use ($options) {
                        return [isset($options[$value]) ? $options[$value] : $value => $count];
                    })->toArray();
                    break;
                case 'boolean':
                    // Convertir 1/0 a Sí/No
                    $data = $answers->countBy()->mapWithKeys(function ($count, $value) {
                        return [$value == 1 ? 'Sí' : 'No' => $count];
                    })->toArray();
                    break;
                case 'checkbox':
                    // Procesar respuestas JSON y mapear IDs a textos
                    $data = $answers
                        ->flatMap(function ($json) use ($options) {
                            $values = json_decode($json) ?: [];
                            return array_map(function ($value) use ($options) {
                                return isset($options[$value]) ? $options[$value] : $value;
                            }, $values);
                        })
                        ->filter()
                        ->countBy()
                        ->toArray();
                    break;
                case 'text':
                case 'textarea':
                    $data = $answers->filter(fn ($value) => !empty(trim($value)))->values()->all();
                    break;
            }

            return [
                'id' => $question->id,
                'text' => $question->text,
                'type' => $question->type,
                'total_answers' => $totalAnswersOnQuestion,
                'results' => $data,
            ];
        });

        // --- CONSTRUCCIÓN DE LA RESPUESTA FINAL DEL API ---
        return response()->json([
            'survey_details' => [
                'title' => $survey->title,
                'description' => $survey->description,
                'start_date' => $survey->start_date,
                'end_date' => $survey->end_date,
            ],
            'kpis' => [
                'total_responses' => $totalResponses,
                'total_questions' => $totalQuestions,
                'first_response_at' => $firstResponseDate,
                'last_response_at' => $lastResponseDate,
            ],
            'questions' => $questionResults,
        ]);
    }

    public function individualResponses(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'gender' => 'nullable|string|in:male,female,other',
            'location_id' => 'nullable|integer|exists:locations,id',
            'question_id' => 'nullable|integer|exists:questions,id',
            'response_value' => 'nullable|string',
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $query = $survey->responses()->with([
            'user:id,name,email,gender,location_id',
            'answers' => function ($q) {
                $q->with(['question:id,text,type', 'question.options:id,text']);
            }
        ]);

        // Aplicar filtros
        if (!empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }
        if (!empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }
        if (!empty($validated['gender'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('gender', $validated['gender']);
            });
        }
        if (!empty($validated['location_id'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('location_id', $validated['location_id']);
            });
        }
        if (!empty($validated['question_id']) && !empty($validated['response_value'])) {
            $query->whereHas('answers', function ($q) use ($validated) {
                $q->where('question_id', $validated['question_id'])
                    ->where(function ($q2) use ($validated) {
                        $q2->where('value', $validated['response_value'])
                            ->orWhereJsonContains('value', $validated['response_value']);
                    });
            });
        }

        $responses = $query->paginate($perPage);

        // Formatear respuestas para el frontend
        $formattedResponses = $responses->map(function ($response) {
            return [
                'id' => $response->id,
                'user' => $response->user ? [
                    'id' => $response->user->id,
                    'name' => $response->user->name,
                    'email' => $response->user->email,
                    'gender' => $response->user->gender,
                    'location_id' => $response->user->location_id,
                ] : null,
                'created_at' => $response->created_at->toIso8601String(),
                'answers' => $response->answers->map(function ($answer) {
                    $value = $answer->question->type === 'checkbox' && is_string($answer->value)
                        ? json_decode($answer->value, true)
                        : $answer->value;
                    return [
                        'question_id' => $answer->question_id,
                        'question_text' => $answer->question->text,
                        'type' => $answer->question->type,
                        'value' => $answer->question->type === 'boolean'
                            ? ($value == 1 ? 'Sí' : 'No')
                            : ($answer->question->type === 'radio' && $answer->question->options->count()
                                ? $answer->question->options->where('id', $value)->first()->text ?? $value
                                : $value),
                    ];
                })->toArray(),
            ];
        });

        return response()->json([
            'data' => $formattedResponses,
            'current_page' => $responses->currentPage(),
            'total' => $responses->total(),
            'per_page' => $responses->perPage(),
            'last_page' => $responses->lastPage(),
        ]);
    }
}

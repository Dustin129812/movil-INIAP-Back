<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AiQueryController extends Controller
{
    public function handleQuery(Request $request)
    {
        set_time_limit(3600); // Mantenemos un tiempo de espera generoso

        $userQuestion = $request->input('question');
        if (!$userQuestion) {
            return response()->json(['error' => 'La pregunta no puede estar vacía.'], 400);
        }

        // --- PASO 1: GENERAR EL SQL ---
        $promptSql = $this->buildSqlPrompt($userQuestion);
        $generatedSql = $this->askOllama($promptSql);

        if (str_contains($generatedSql, 'error')) {
            return response()->json(json_decode($generatedSql, true), 500);
        }

        // --- PASO 2: EJECUTAR EL SQL ---
        try {
            // Nos aseguramos de que termine en punto y coma, pero solo uno.
            $cleanSql = rtrim(trim($generatedSql), ';') . ';';
            $results = DB::select($cleanSql);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'La consulta SQL generada no es válida.',
                'generated_sql' => $generatedSql, // Mostramos el SQL original "sucio" para depurar
                'cleaned_sql' => $cleanSql ?? '', // Mostramos el SQL que intentamos ejecutar
                'prompt_sent_to_ai' => $promptSql,
                'details' => $e->getMessage()
            ], 400);
        }

        // --- PASO 3: HUMANIZAR LA RESPUESTA ---
        $promptHumanize = $this->buildHumanizePrompt($userQuestion, $results);
        $naturalLanguageResponse = $this->askOllama($promptHumanize);

        // --- PASO 4: DEVOLVER LA RESPUESTA FINAL ---
        return response()->json([
            'answer' => $naturalLanguageResponse,
            'debug_info' => [
                'question' => $userQuestion,
                'generated_sql' => $cleanSql,
                'raw_results' => $results
            ]
        ]);
    }

    /**
     * Envía un prompt a Ollama, limpia la respuesta y la devuelve.
     */
    private function askOllama(string $prompt): string
    {
        try {
            // Usa la URL de localtunnel que te dio Colab
            $ollamaUrl = 'https://six-webs-matter.loca.lt/api/generate';

            $response = Http::withoutVerifying() // Mantenemos esto por si hay problemas de SSL
            ->withHeaders([
                // --- ¡AQUÍ ESTÁ LA NUEVA LÍNEA MÁGICA! ---
                'bypass-tunnel-reminder' => 'true' // Esta cabecera salta la página de advertencia.
            ])
                ->timeout(3600)
                ->post($ollamaUrl, [
                    'model'   => 'gemma:2b',
                    'prompt'  => $prompt,
                    'stream'  => false,
                ]);

            if (!$response->successful()) {
                // Devolvemos el cuerpo del error para tener más pistas
                return json_encode(['error' => 'Error al contactar el modelo de IA.', 'details' => $response->body()]);
            }

            $rawResponse = trim($response->json()['response']);

            if (preg_match('/```(?:sql)?\s*(.*?)\s*```/s', $rawResponse, $matches)) {
                return trim($matches[1]);
            }

            return $rawResponse;

        } catch (\Exception $e) {
            return json_encode(['error' => 'No se pudo conectar con Ollama o la petición tardó demasiado.', 'details' => $e->getMessage()]);
        }
    }


    /**
     * Construye el prompt para generar la consulta SQL.
     */
    private function buildSqlPrompt(string $question): string
    {
        // Leemos el contexto detallado desde el archivo
        $schemaContext = Storage::disk('local')->get('schema_context.txt');
        $today = now()->toDateTimeString();

        return "
    Eres un asistente experto en bases de datos PostgreSQL. Tu objetivo es convertir la pregunta de un usuario en una única y precisa consulta SQL.

    ### INSTRUCCIONES IMPORTANTES ###
    1.  **Analiza el Contexto:** A continuación se te proporciona un contexto detallado del esquema de la base de datos, incluyendo reglas de negocio, sinónimos y relaciones (claves foráneas). Debes basarte ESTRICTAMENTE en esta información.
    2.  **Usa los Sinónimos:** Si la pregunta del usuario usa un sinónimo (ej: 'proyecto'), tradúcelo al nombre correcto de la tabla o columna (ej: `products`) como se indica en las reglas.
    3.  **Respeta las Relaciones:** Utiliza las claves foráneas para crear las uniones (`JOIN`) necesarias entre tablas cuando una pregunta involucre múltiples conceptos (ej: usuarios y sus proyectos).
    4.  **Aplica las Reglas de Negocio:** Sigue todas las reglas de negocio, especialmente la de usar `ILIKE '%...%'` para búsquedas de texto flexibles.
    5.  **Respuesta Final:** Tu única salida debe ser el código SQL puro, sin explicaciones ni formato Markdown.

    ### CONTEXTO (Fuente de la Verdad) ###
    $schemaContext
    ### FIN DEL CONTEXTO ###

    - La fecha y hora actual es: $today.

    Pregunta del usuario: \"$question\"
    Consulta SQL:
    ";
    }

    /**
     * Construye el prompt para convertir los datos en una respuesta amigable.
     */
    private function buildHumanizePrompt(string $question, array $results): string
    {
        $resultsJson = json_encode($results);
        return "Eres un asistente de datos amigable. Tu tarea es responder la pregunta original del usuario de forma natural y concisa, basándote en los datos que te proporciono. No menciones que viste datos JSON o una consulta SQL. Actúa como si tú mismo hubieras encontrado la respuesta. Responde en español.\n\nPregunta original del usuario: \"$question\"\n\nDatos encontrados (en formato JSON):\n$resultsJson\n\nRespuesta amigable:";
    }
}

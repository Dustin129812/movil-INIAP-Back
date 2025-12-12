<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerController extends Controller
{
    // --- CONFIGURACIÓN DE BOTPRESS Y META ---
    private $botpressUrl = 'https://data.iniap.gob.ec/bot/api/v1/bots/iniap/converse/';
    private $metaApiVersion = 'v24.0'; // Usaremos la versión 24 de Meta
    private $verifyToken = 'iniap_secreto_v12'; // El token que pones en Meta
    private $pageAccessToken = 'EAAZCUnZCpJFWwBQFgtEuMqYL5vnC6y4GspLwzdYer6KLBsnwJwQQJYyb8ZBOFYmD0PtWfzM9gChZBrC4L43gID1wP8hDIuhoZCqBSrZCJyxEvJkzzvtAvX4U6C4JGodaIhUxIskjdykexKOuKL3vFoc4shPdmPVCD49PqixURC0Y80dLUWJnVpmeVTmZBpbenbWn2BAVd5n'; // ¡El nuevo token permanente!

    // ----------------------------------------------------------------------------------

    // 1. Maneja la verificación inicial (GET)
    public function verify(Request $request)
    {
        $hubMode = $request->get('hub_mode');
        $verifyToken = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($hubMode === 'subscribe' && $verifyToken === $this->verifyToken) {
            // Devuelve el hub_challenge que pide Meta
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Verification failed: Token mismatch', 403);
    }

    // 2. Maneja los mensajes entrantes (POST)
    public function handleMessage(Request $request)
    {
        $data = $request->all();

        if (isset($data['object']) && $data['object'] === 'page') {
            foreach ($data['entry'] as $entry) {
                foreach ($entry['messaging'] as $messagingEvent) {

                    $senderId = $messagingEvent['sender']['id'];
                    $messageText = null;

                    // Si es un mensaje de texto normal (usuario escribió)
                    if (isset($messagingEvent['message']['text'])) {
                        $messageText = $messagingEvent['message']['text'];
                    }
                    // Si es un postback (clic en un botón de opción rápida)
                    elseif (isset($messagingEvent['postback']['payload'])) {
                        $messageText = $messagingEvent['postback']['payload'];
                    }
                    // Si es un quick_reply (clic en un botón de opción rápida)
                    elseif (isset($messagingEvent['message']['quick_reply']['payload'])) {
                        $messageText = $messagingEvent['message']['quick_reply']['payload'];
                    }

                    if ($messageText) {
                        // 1. Enviar el mensaje/payload a Botpress (Converse API)
                        $botpressResponse = Http::post($this->botpressUrl . $senderId, [
                            'type' => 'text',
                            'text' => $messageText,
                        ]);

                        // 2. Procesar la respuesta de Botpress y enviarla a Meta
                        if ($botpressResponse->successful()) {
                            $responses = $botpressResponse->json()['responses'] ?? [];

                            foreach ($responses as $response) {
                                $this->processBotpressResponse($senderId, $response);
                            }
                        }
                    }
                }
            }
        }

        // Meta siempre espera una respuesta 200 OK
        return response('EVENT_RECEIVED', 200);
    }

    // --- Método Mejorado FINAL para procesar Botpress Responses ---

    private function processBotpressResponse($recipientId, $response)
    {
        $messageData = [];
        $isQuickReply = false;
        $textForQR = $response['text'] ?? null;
        $choices = [];

        // --- 1. MANEJO DEL FORMATO "single-choice" (La Skill Choice) ---
        if ($response['type'] === 'single-choice' && isset($response['choices'])) {
            $isQuickReply = true;
            $choices = $response['choices'];
            // Usamos el texto del mensaje anterior o el texto de la opción (Investigación científica)
            $textForQR = 'Por favor selecciona una de las siguientes opciones:';
        }

        // --- 2. MANEJO DE QUICK REPLIES ESTÁNDAR (Si el bot las envía así) ---
        elseif (isset($response['quick_replies'])) {
            $isQuickReply = true;
            $choices = $response['quick_replies'];
        }

        // --- CONSTRUCCIÓN DEL PAYLOAD SI HAY OPCIONES ---
        if ($isQuickReply) {
            $quickReplies = [];

            // Mapear la lista de opciones (ya sean de 'choices' o 'quick_replies')
            foreach ($choices as $reply) {
                $quickReplies[] = [
                    'content_type' => 'text',
                    'title' => $reply['title'] ?? $reply['text'], // Usa 'title' o 'text'
                    'payload' => $reply['value'] ?? $reply['payload'] ?? $reply['text'] // Usa 'value' (de choice) o 'payload'
                ];
            }

            $messageData = [
                'text' => $textForQR,
                'quick_replies' => $quickReplies
            ];
        }

        // --- 3. MANEJO DE TEXTO SIMPLE (solo si no hay quick replies) ---
        elseif ($response['type'] === 'text' && !empty($response['text'])) {
            $messageData = [
                'text' => $response['text']
            ];
        }

        // Enviamos solo si hay contenido que enviar
        if (!empty($messageData)) {
            $this->sendFacebookPayload($recipientId, $messageData);
        }
    }
// --- Fin del Método processBotpressResponse ---

    // 4. Envía el mensaje (payload) al usuario a través de la API de Meta
    private function sendFacebookPayload($recipientId, $messageData)
    {
        $apiUrl = "https://graph.facebook.com/{$this->metaApiVersion}/me/messages";

        $response = Http::withToken($this->pageAccessToken)->post($apiUrl, [
            'recipient' => ['id' => $recipientId],
            'message' => $messageData // Enviamos el JSON de mensaje completo
        ]);

        if (!$response->successful()) {
            // Opcional: Loggear el error si Meta rechaza el formato
            Log::error('Meta Send Failed', $response->json());
        }
    }
}

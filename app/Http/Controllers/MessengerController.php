<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerController extends Controller
{

    public function verify(Request $request)
    {
        $hubMode = $request->get('hub_mode');
        $verifyToken = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($hubMode === 'subscribe' && $verifyToken === $this->verifyToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Verification failed: Token mismatch', 403);
    }

    public function handleMessage(Request $request)
    {
        $data = $request->all();

        if (isset($data['object']) && $data['object'] === 'page') {
            foreach ($data['entry'] as $entry) {
                foreach ($entry['messaging'] as $messagingEvent) {

                    $senderId = $messagingEvent['sender']['id'];
                    $messageText = null;

                    if (isset($messagingEvent['message']['quick_reply']['payload'])) {
                        $messageText = $messagingEvent['message']['quick_reply']['payload'];
                    }
                    elseif (isset($messagingEvent['postback']['payload'])) {
                        $messageText = $messagingEvent['postback']['payload'];
                    }
                    // 3. Prioridad: Texto escrito por usuario ("Danny")
                    elseif (isset($messagingEvent['message']['text'])) {
                        $messageText = $messagingEvent['message']['text'];
                    }

                    if ($messageText) {
                        $botpressResponse = Http::post($this->botpressUrl . $senderId, [
                            'type' => 'text',
                            'text' => $messageText
                        ]);

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
        return response('EVENT_RECEIVED', 200);
    }

    // --- Método Mejorado FINAL para procesar Botpress Responses ---

    private function processBotpressResponse($recipientId, $response)
    {
        $messageData = [];
        $isQuickReply = false;
        $textForQR = null;
        $choices = [];

        //  MANEJO DEL FORMATO "single-choice"
        if ($response['type'] === 'single-choice' && isset($response['choices'])) {
            $isQuickReply = true;
            $choices = $response['choices'];
            $textForQR = $response['text'] ?? 'Selecciona una opción:';
        }
        //  MANEJO DE QUICK REPLIES ESTÁNDAR
        elseif (isset($response['quick_replies'])) {
            $isQuickReply = true;
            $choices = $response['quick_replies'];
            $textForQR = $response['text'] ?? 'Opciones:';
        }

        if ($isQuickReply) {
            $quickReplies = [];

            foreach ($choices as $reply) {
                $fullTitle = $reply['title'] ?? $reply['text'];

                $payloadToSend = $fullTitle;

                $displayTitle = mb_strlen($fullTitle) > 20
                    ? mb_substr($fullTitle, 0, 17) . '...'
                    : $fullTitle;

                $quickReplies[] = [
                    'content_type' => 'text',
                    'title' => $displayTitle,
                    'payload' => $payloadToSend
                ];
            }

            $messageData = [
                'text' => $textForQR,
                'quick_replies' => $quickReplies
            ];
        }
        // MANEJO DE TEXTO SIMPLE
        elseif ($response['type'] === 'text' && !empty($response['text'])) {
            $messageData = [
                'text' => $response['text']
            ];
        }

        if (!empty($messageData)) {
            $this->sendFacebookPayload($recipientId, $messageData);
        }
    }

    private function sendFacebookPayload($recipientId, $messageData)
    {
        $apiUrl = "https://graph.facebook.com/{$this->metaApiVersion}/me/messages";

        $response = Http::withToken($this->pageAccessToken)->post($apiUrl, [
            'recipient' => ['id' => $recipientId],
            'message' => $messageData
        ]);

        if (!$response->successful()) {
            Log::error('Meta Send Failed', $response->json());
        }
    }
}

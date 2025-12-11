<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    // Cargamos la configuración desde el .env para seguridad
    private $botpressUrl;
    private $botId;
    private $fbPageToken;
    private $verifyToken;

	public function __construct()
    {
        // USAR config() EN LUGAR DE env()
        $this->botpressUrl = config('services.botpress.url', 'http://localhost:3000');
        $this->botId       = config('services.botpress.bot_id', 'iniap');
        $this->fbPageToken = config('services.facebook.page_token');
        $this->verifyToken = config('services.facebook.verify_token');
    }
    /**
     * 1. Verificación del Webhook (Requisito de Facebook)
     */

	public function verifyWebhook(Request $request)
    {
        $mode = $request->input('hub_mode') ?? $request->input('hub.mode');
        $token = $request->input('hub_verify_token') ?? $request->input('hub.verify_token');
        $challenge = $request->input('hub_challenge') ?? $request->input('hub.challenge');

        if ($mode && $token) {
            // Ahora $this->verifyToken SÍ tendrá valor
            if ($mode === 'subscribe' && $token === $this->verifyToken) {
                Log::info("Webhook verificado correctamente.");
                return response($challenge, 200);
            }
        }
        
        Log::warning("Fallo verificación. Token esperado: " . $this->verifyToken . " - Recibido: " . $token);
        return response('Forbidden', 403);
    }

    /**
     * 2. Manejo del Mensaje Entrante (POST)
     */
    public function handleMessage(Request $request)
    {
        $data = $request->all();

        // Verificar que sea un evento de página
        if (isset($data['object']) && $data['object'] === 'page') {
            foreach ($data['entry'] as $entry) {
                // Iterar sobre los eventos de mensajería
                if (!isset($entry['messaging'])) continue;

                foreach ($entry['messaging'] as $event) {
                    $sender_psid = $event['sender']['id'];
                    $userMessage = null;

                    // CASO A: Mensaje de Texto normal
                    if (isset($event['message']['text'])) {
                        $userMessage = $event['message']['text'];
                    }
                    // CASO B: Clic en botón / Menú persistente (Postback)
                    else if (isset($event['postback']['payload'])) {
                        $userMessage = $event['postback']['payload'];
                    }

                    // Si tenemos un mensaje válido, procesamos
                    if ($userMessage) {
                        // (Opcional) Efecto de "Escribiendo..."
                        $this->sendTypingAction($sender_psid);

                        // 1. Enviar a Botpress
                        $botReplies = $this->sendToBotpress($sender_psid, $userMessage);

                        // 2. Procesar respuestas de Botpress y enviar a Facebook
                        foreach ($botReplies as $reply) {
                            $this->processAndSendReply($sender_psid, $reply);
                        }
                    }
                }
            }
            return response('EVENT_RECEIVED', 200);
        }

        return response('Not Found', 404);
    }

    /**
     * 3. Comunicación con Botpress (Converse API)
     */
    private function sendToBotpress($userId, $text)
    {
        // Nota: Usamos localhost porque Laravel y Botpress están en el mismo servidor/red local
        $url = "{$this->botpressUrl}/api/v1/bots/{$this->botId}/converse/{$userId}";

        try {
            $response = Http::post($url, [
                'type' => 'text',
                'text' => $text,
                'includedContexts' => ['global']
            ]);

            return $response->json()['responses'] ?? [];

        } catch (\Exception $e) {
            Log::error("Error Botpress: " . $e->getMessage());
            return [['type' => 'text', 'text' => 'Lo siento, estoy teniendo problemas de conexión interna.']];
        }
    }

    /**
     * 4. Procesar tipos de respuesta (Texto, Imagen, Opciones)
     */
    private function processAndSendReply($recipientId, $reply)
    {
        $messageData = [];

        switch ($reply['type']) {
            case 'text':
                $messageData = ['text' => $reply['text']];
                break;

            case 'image':
                $messageData = [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => $reply['image'],
                            'is_reusable' => true
                        ]
                    ]
                ];
                break;

            case 'single-choice': // Botones de opciones rápidas
                $quickReplies = [];
                foreach ($reply['choices'] as $choice) {
                    // Facebook permite max 20 caracteres en título de quick replies a veces, cuidado con textos largos
                    $quickReplies[] = [
                        'content_type' => 'text',
                        'title' => substr($choice['title'], 0, 20),
                        'payload' => $choice['value']
                    ];
                }
                $messageData = [
                    'text' => $reply['text'],
                    'quick_replies' => $quickReplies
                ];
                break;

            default:
                // Fallback para tipos no soportados
                if(isset($reply['text'])) {
                    $messageData = ['text' => $reply['text']];
                }
                break;
        }

        if (!empty($messageData)) {
            $this->sendToFacebook($recipientId, $messageData);
        }
    }

    /**
     * 5. Enviar Payload final a Facebook API
     */
    private function sendToFacebook($recipientId, $messageData)
    {
        $url = "https://graph.facebook.com/v18.0/me/messages?access_token={$this->fbPageToken}";

        $body = [
            'recipient' => ['id' => $recipientId],
            'message' => $messageData,
            'messaging_type' => 'RESPONSE'
        ];

        try {
            Http::post($url, $body);
        } catch (\Exception $e) {
            Log::error("Error enviando a Facebook: " . $e->getMessage());
        }
    }

    /**
     * Extra: Indicador de "Escribiendo..."
     */
    private function sendTypingAction($recipientId)
    {
        $url = "https://graph.facebook.com/v18.0/me/messages?access_token={$this->fbPageToken}";
        Http::post($url, [
            'recipient' => ['id' => $recipientId],
            'sender_action' => 'typing_on'
        ]);
    }
}

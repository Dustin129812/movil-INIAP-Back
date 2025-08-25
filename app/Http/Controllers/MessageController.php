<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function list($conversationId)
    {
        $messages = Conversation::findOrFail($conversationId)->messages()->with('sender:id,name')->get();
        return response()->json($messages);
    }

    public function store(Request $request, $conversationId)
    {
        Log::info('Iniciando MessageController::store', [
            'conversation_id' => $conversationId,
            'user_id' => $request->user()?->id,
            'headers' => $request->headers->all(),
        ]);

        $request->validate(['content' => 'required|string']);

        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        $message = $conversation->messages()->create([
            'content' => $request->input('content'),
            'sender_id' => $user?->id,
        ]);

        Log::info('Mensaje creado, disparando evento MessageSent:', [
            'message_id' => $message->id,
            'conversation_id' => $conversationId,
            'sender_id' => $user?->id,
        ]);

        broadcast(new MessageSent($message));

        Log::info('Evento MessageSent enviado al canal:', [
            'channel' => 'conversation.' . $conversationId,
            'message_id' => $message->id,
        ]);

        return response()->json($message->load('sender:id,name'));
    }
}

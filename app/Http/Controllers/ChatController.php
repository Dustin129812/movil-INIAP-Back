<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatController extends Controller
{

    public function getMessages(Conversation $conversation)
    {
        return response()->json($conversation->messages);
    }
    public function startConversation(Request $request)
    {
        $user = null;
        try {
            if (JWTAuth::getToken()) {
                $user = JWTAuth::parseToken()->authenticate();
            }
        } catch (Exception $e) {
        $user = null;
    }

    $guestIdForConversation = $user ? null : Str::uuid()->toString();

    $conversation = Conversation::create([
        'user_id' => $user ? $user->id : null,
        'guest_id' => $guestIdForConversation,
        'status' => 'open',
    ]);

    return response()->json([
        'conversation_id' => $conversation->id,
        'guest_id' => $guestIdForConversation
    ]);
}

    // Enviar mensaje
    public function sendMessage(Request $request, $conversationId) // Acepta el ID desde la URL
    {
        // 1. Validar solo el contenido del mensaje
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        // 2. Determinar quién envía el mensaje
        $user = null;
        try {
            if (JWTAuth::getToken()) {
                $user = JWTAuth::parseToken()->authenticate();
            }
        } catch (\Exception $e) {
            $user = null;
        }

        // 3. Obtener el guest_id desde la conversación existente
        $conversation = Conversation::findOrFail($conversationId);
        $guestId = $user ? null : $conversation->guest_id;

        // 4. Crear el mensaje usando el $conversationId de la URL
        $message = Message::create([
            'conversation_id' => $conversationId,
            'content' => $request->content,
            'sender_type' => $user ? ($user->is_admin ? 'administrador' : 'user') : 'guest',
            'sender_id' => $user ? $user->id : null,
            'guest_id' => $guestId,
        ]);

        // 5. Enviar el evento de broadcast
        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => $message]);
    }

    // Cerrar conversación
    public function closeConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->update(['status' => 'closed']);
        return response()->json(['message' => 'Conversation closed']);
    }

    // Reasignar conversación
    public function reassignConversation(Request $request, $conversationId)
    {
        $request->validate(['admin_id' => 'required|exists:users,id']);
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->update(['admin_id' => $request->admin_id, 'status' => 'reassigned']);
        return response()->json(['message' => 'Conversation reassigned']);
    }

    public function listConversations(Request $request)
    {
        $user = JWTAuth::user();

        if (!$user || !$user->hasRole('administrador')) {
            return response()->json(['error' => 'Forbidden. Administrator access required.'], 403);
        }

        $conversations = Conversation::query()
            ->select('id', 'user_id', 'guest_id', 'admin_id', 'status', 'created_at')
            ->with(['user:id,name', 'admin:id,name']) // Optimizado para cargar solo id y name
            ->get()
            ->groupBy('status');

        $statuses = ['open', 'in-progress', 'reassigned', 'closed'];
        $groupedConversations = [];
        foreach ($statuses as $status) {
            $groupedConversations[$status] = $conversations[$status] ?? [];
        }

        return response()->json($groupedConversations);
    }

    public function listAdmins()
    {
        // Busca todos los usuarios que tengan el rol 'administrador'
        $admins = User::role('administrador')->get();

        // Devuelve solo la información necesaria (ID y nombre) para el dropdown
        return response()->json($admins->map(function ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
            ];
        }));
    }
}

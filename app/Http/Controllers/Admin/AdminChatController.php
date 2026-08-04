<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Investigacion\Entities\Conversation;
use Modules\Investigacion\Events\MessageSent;
use Tymon\JWTAuth\Facades\JWTAuth;

// ¡Asegúrate de importar JWTAuth!

class AdminChatController extends Controller
{
    /**
     * Lista todas las conversaciones para el panel de administración.
     */
    public function listConversations()
    {
        // La autorización ya la maneja el middleware de la ruta, por lo que no es necesaria aquí.
        $conversations = Conversation::query()
            ->select('id', 'user_id', 'guest_id', 'admin_id', 'status', 'created_at')
            ->with(['user:id,name', 'admin:id,name']) // Carga solo los campos necesarios
            ->get()
            ->groupBy('status');

        // Asegura que todos los estados posibles existan en la respuesta
        $statuses = ['open', 'in-progress', 'reassigned', 'closed'];
        $groupedConversations = [];
        foreach ($statuses as $status) {
            $groupedConversations[$status] = $conversations->get($status, []);
        }

        return response()->json($groupedConversations);
    }

    /**
     * Devuelve una lista de todos los usuarios que tienen el rol de administrador.
     */
    public function listAdmins()
    {
        $admins = User::role('administrador')->get(['id', 'name']);

        return response()->json($admins);
    }

    /**
     * Obtiene los mensajes de una conversación específica.
     */
    public function getMessages(Conversation $conversation)
    {
        return response()->json($conversation->messages()->with('sender:id,name')->get());
    }

    /**
     * Permite a un administrador enviar un mensaje a una conversación.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate(['content' => 'required|string|max:1000']);
        $adminUser = JWTAuth::user();

        $message = $conversation->messages()->create([
            'content' => $request->content,
            'sender_type' => 'admin',
            'sender_id' => $adminUser->id,
        ]);

        broadcast(new MessageSent($message));

        return response()->json($message, 201);
    }
}

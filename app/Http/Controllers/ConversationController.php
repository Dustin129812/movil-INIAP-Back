<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $guestId = $request->header('X-Guest-Id');
        $response = [];

        // LÓGICA MEJORADA: El usuario autenticado SIEMPRE tiene prioridad.
        if ($user) {
            // Si hay un usuario logueado, buscamos o creamos su conversación.
            $conversation = Conversation::firstOrCreate(['user_id' => $user->id]);
        } else {
            // Si NO hay usuario, entonces es un invitado.
            $guestId = $guestId ?? (string) Str::uuid();
            $conversation = Conversation::firstOrCreate(['guest_id' => $guestId]);

            // Solo devolvemos el guest_id si es una nueva conversación de invitado.
            $response['guest_id'] = $conversation->guest_id;
        }

        $response['conversation'] = $conversation;
        return response()->json($response);
    }

    public function index()
    {
        // Eager load para eficiencia: carga el usuario y el último mensaje.
        $conversations = Conversation::with(['user:id,name', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }])->latest()->get();

        return response()->json($conversations);
    }
}

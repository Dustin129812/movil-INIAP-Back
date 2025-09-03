<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;

Broadcast::channel('conversation.{id}', function ($user, $id) {
    // Verifica si el usuario autenticado tiene acceso a la conversación
    $conversation = Conversation::find($id);
    if (!$conversation) {
        return false; // Conversación no encontrada
    }

    // Permitir si el usuario es el propietario (user_id) o el admin (admin_id)
    return Auth::check() && ($conversation->user_id == $user->id || $conversation->admin_id == $user->id);
});

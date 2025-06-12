<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Devuelve las notificaciones del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications,
            'read' => $user->readNotifications()->paginate(15), // Paginamos las leídas
        ]);
    }

    public function markAsRead(Request $request, $notificationId)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notificación marcada como leída.']);
        }

        return response()->json(['message' => 'Notificación no encontrada.'], 404);
    }

    /**
     * Marca todas las notificaciones no leídas como leídas.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Todas las notificaciones han sido marcadas como leídas.']);
    }
}

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

    public function markAsReadBatch(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'required|string', // Los IDs de las notificaciones son UUIDs (strings)
        ]);

        $user = $request->user();
        $notificationIds = $request->input('notification_ids');

        $user->notifications()
            ->whereIn('id', $notificationIds)
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }

    public function markAsUnreadBatch(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'required|string',
        ]);

        $user = $request->user();
        $notificationIds = $request->input('notification_ids');

        $user->notifications()
            ->whereIn('id', $notificationIds)
            ->update(['read_at' => null]);

        return response()->json(['message' => 'Notificaciones marcadas como no leídas.']);
    }

    public function destroyBatch(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'required|string',
        ]);

        $user = $request->user();
        $notificationIds = $request->input('notification_ids');

        // OJO: Esto las ELIMINA. Si quieres "archivar", deberías añadir una columna `archived_at`
        // a tu tabla `notifications` y actualizarla aquí en lugar de usar `delete()`.
        $user->notifications()
            ->whereIn('id', $notificationIds)
            ->delete(); // O ->update(['archived_at' => now()]) si implementas archivado

        return response()->json(['message' => 'Notificaciones eliminadas.']);
    }
}

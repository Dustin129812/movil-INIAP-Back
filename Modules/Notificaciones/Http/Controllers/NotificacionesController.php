<?php

namespace Modules\Notificaciones\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Notificaciones\Services\NotificationManagementService;
use Modules\Notificaciones\Transformers\NotificationResource;

class NotificacionesController extends Controller
{
    public function __construct(
        private readonly NotificationManagementService $notificationService
    ) {}

    /**
     * Obtiene las notificaciones del usuario autenticado (paginadas).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications() // Usa la relación polimórfica nativa de Laravel
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function markAsRead(Request $request, string $id): NotificationResource
    {
        $notification = $this->notificationService->markAsRead($request->user(), $id);

        return new NotificationResource($notification);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'Todas las notificaciones han sido marcadas como leídas.'
        ]);
    }
}

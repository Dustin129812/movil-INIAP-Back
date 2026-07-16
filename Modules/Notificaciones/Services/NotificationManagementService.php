<?php

namespace Modules\Notificaciones\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Notificaciones\Entities\Notification;

class NotificationManagementService
{
    public function markAsRead(User $user, string $notificationId): Notification
    {
        return DB::transaction(function () use ($user, $notificationId) {
            $notification = $user->notifications()->findOrFail($notificationId);

            $notification->update(['read_at' => now()]);

            return $notification;
        });
    }

    public function markAllAsRead(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->unreadNotifications()->update(['read_at' => now()]);
        });
    }
}

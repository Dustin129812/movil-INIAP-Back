<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str; // ¡Importa Str!

class CreateActivity extends Notification
{
    use Queueable;

    public $activity;
    public $updater;

    public function __construct(Activity $activity, User $updater)
    {
        $this->activity = $activity;
        $this->updater = $updater;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $title = "Nueva Actividad Asignada: {$this->activity->description}";
        $fullMessage = "La actividad '{$this->activity->description}' ha sido asignada a su cargo por {$this->updater->name}.";
        $messagePreview = Str::limit($fullMessage, 150, '...');

        return [
            // Campos esperados por el frontend
            'id' => $this->id,
            'type' => 'activity_assignment', // Tipo específico
            'title' => $title,
            'body_preview' => $messagePreview,
            'full_body' => $fullMessage,

            // Datos específicos
            'activity_id' => $this->activity->id,
            'activity_name' => $this->activity->description,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'action_url' => '/dashboard/activities/' . $this->activity->id, // Ejemplo: URL a la actividad
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return [
            'activity_id' => $this->activity->id,
            'activity_name' => $this->activity->description,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'message' => "La actividad '{$this->activity->description}' ha sido asignada a su cargo por {$this->updater->name}.",
        ];
    }
}

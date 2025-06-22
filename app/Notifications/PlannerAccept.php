<?php

namespace App\Notifications;

use App\Models\WeekActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PlannerAccept extends Notification
{
    use Queueable;

    public $weekActivity;
    public $approver;
    public $status;

    public function __construct(WeekActivity $weekActivity, User $approver, string $status)
    {
        $this->weekActivity = $weekActivity;
        $this->approver = $approver;
        $this->status = $status;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusText = strtoupper($this->status) === 'APPROVED' ? '*APROBADA*' : '*RECHAZADA*';

        return [
            'week_activity_id' => $this->weekActivity->id,
            'description' => $this->weekActivity->description,
            'approved_id' => $this->approver->id,
            'approved_name' => $this->approver->name,
            'status' => $this->status,
            'message' => "La planificación semanal '{$this->weekActivity->description}' fue {$statusText} por {$this->approver->name}.",
        ];
    }
}

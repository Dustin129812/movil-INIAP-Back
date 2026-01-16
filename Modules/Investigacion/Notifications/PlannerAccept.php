<?php

namespace Modules\Investigacion\Notifications;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Investigacion\Entities\WeekActivity;

// ¡Importa Str para usar Str::limit!

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
        $statusText = strtoupper($this->status) === 'APPROVED' ? 'APROBADA' : 'RECHAZADA';
        $statusEmoji = strtoupper($this->status) === 'APPROVED' ? '✅' : '❌';

        $title = "Planificación Semanal {$statusEmoji} - {$statusText}";
        $dateFormatted = $this->weekActivity->date
            ? Carbon::parse($this->weekActivity->date)->format('d/m/Y')
            : 'Fecha desconocida';

        $fullMessage = "La planificación semanal '{$this->weekActivity->description}' ha sido {$statusText} por {$this->approver->name}.";
        $fullMessage .= " Actividad: {$this->weekActivity->activity->description} el {$dateFormatted}. Horas estimadas: {$this->weekActivity->estimated_hours}.";

        $messagePreview = \Illuminate\Support\Str::limit($fullMessage, 150, '...');

        return [
            'id' => $this->id,
            'type' => 'planner_approval',
            'title' => $title,
            'body_preview' => $messagePreview,
            'full_body' => $fullMessage,
            'week_activity_id' => $this->weekActivity->id,
            'description' => $this->weekActivity->description,
            'approved_id' => $this->approver->id,
            'approved_name' => $this->approver->name,
            'status' => $this->status,
            'created_at' => now()->toDateTimeString(),
            'action_url' => '/dashboard/week-activities/' . $this->weekActivity->id,
        ];
    }
}

<?php

namespace Modules\Investigacion\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BulkPlannerAccept extends Notification
{
    use Queueable;

    public function __construct(
        public Collection $activities,
        public User $approver,
        public string $status
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $count = $this->activities->count();
        $isApproved = strtoupper($this->status) === 'APPROVED';

        $statusText = $isApproved ? 'APROBADAS' : 'RECHAZADAS';
        $statusEmoji = $isApproved ? '✅' : '❌';

        $itemWord = $count === 1 ? 'actividad' : 'actividades';
        $title = "Planificación {$statusEmoji} - {$count} {$itemWord} {$statusText}";

        $activityNames = $this->activities->pluck('description')->take(3)->implode(', ');
        $moreText = $count > 3 ? " y " . ($count - 3) . " más." : ".";

        $fullMessage = "{$this->approver->name} ha " . strtolower($statusText) . " {$count} {$itemWord} de su planificación semanal: {$activityNames}{$moreText}";

        return [
            'type' => 'bulk_planner_approval',
            'title' => $title,
            'body_preview' => Str::limit($fullMessage, 150, '...'),
            'full_body' => $fullMessage,

            'count' => $count,
            'status' => $this->status,
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->name,

            'activity_ids' => $this->activities->pluck('id')->toArray(),

            'action_url' => '/dashboard/week-activities',
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

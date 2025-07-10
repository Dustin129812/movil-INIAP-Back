<?php

namespace App\Notifications;

use App\Models\WeekActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // ¡Importa Str para usar Str::limit!

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
        $statusEmoji = strtoupper($this->status) === 'APPROVED' ? '✅' : '❌'; // Emoji para el título

        $title = "Planificación Semanal {$statusEmoji} - {$statusText}";
        $fullMessage = "La planificación semanal '{$this->weekActivity->description}' ha sido {$statusText} por {$this->approver->name}.";

        // Puedes añadir más detalles al mensaje completo si quieres, por ejemplo:
        if (strtoupper($this->status) === 'RECHAZADA') {
            // Si tu WeekActivity tiene un campo para razones de rechazo
            // $fullMessage .= " Razón: {$this->weekActivity->rejection_reason}.";
        }
        $fullMessage .= " Actividad: {$this->weekActivity->activity->description} el {$this->weekActivity->date->format('d/m/Y')}. Horas estimadas: {$this->weekActivity->estimated_hours}.";


        $messagePreview = Str::limit($fullMessage, 150, '...'); // Limita a 150 caracteres para la vista previa

        return [
            // Campos esperados por el frontend
            'id' => $this->id, // Aunque Laravel lo añade, es buena práctica incluirlo
            'type' => 'planner_approval', // Un tipo específico para esta notificación
            'title' => $title, // Título dinámico para la lista
            'body_preview' => $messagePreview, // La versión corta del mensaje
            'full_body' => $fullMessage, // El mensaje completo para la vista de detalle

            // Datos específicos de la notificación
            'week_activity_id' => $this->weekActivity->id,
            'description' => $this->weekActivity->description,
            'approved_id' => $this->approver->id,
            'approved_name' => $this->approver->name,
            'status' => $this->status, // Mantén el status original también
            'created_at' => now()->toDateTimeString(), // Asegúrate de enviar la fecha de creación
            // Opcional: Si quieres un enlace directo a la actividad de planificación
            'action_url' => '/dashboard/week-activities/' . $this->weekActivity->id,
        ];
    }
}

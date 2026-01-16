<?php

namespace Modules\Investigacion\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\Investigacion\Entities\Activity;

// Asumo que $this->activity es el modelo Activity
// Importa Str para usar Str::limit

class RateWeeklyActivityNo extends Notification
{
    use Queueable;

    public $activity; // Esta es la actividad general (no la WeekActivity)
    public $investigator; // La persona que califica (ej. el responsable del producto o supervisor)
    public $percentage;   // El porcentaje de cumplimiento reportado
    public $observations; // Las observaciones/justificante

    public function __construct(Activity $activity, User $investigator, int $percentage, ?string $observations = null)
    {
        $this->activity = $activity;
        $this->investigator = $investigator;
        $this->percentage = $percentage;
        $this->observations = $observations;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Determinar el estado y el emoji
        $isNotCompleted = $this->percentage === 0;
        $isPartiallyCompleted = $this->percentage > 0 && $this->percentage < 100;

        $statusEmoji = '⚠️'; // Advertencia general
        $statusText = '';
        $titlePrefix = '';

        if ($isNotCompleted) {
            $statusText = "NO REALIZADA";
            $titlePrefix = "Actividad No Realizada";
            $statusEmoji = '❌'; // Cruz para no realizada
        } elseif ($isPartiallyCompleted) {
            $statusText = "INCOMPLETA ({$this->percentage}%)";
            $titlePrefix = "Actividad Incompleta";
            $statusEmoji = '🟡'; // Círculo amarillo para incompleta
        } else {
            // Este caso no debería ocurrir si la notificación es 'No'
            $statusText = "CALIFICADA ({$this->percentage}%)";
            $titlePrefix = "Actividad Calificada";
            $statusEmoji = 'ℹ️';
        }

        // --- Título para la lista de notificaciones ---
        $title = "{$statusEmoji} {$titlePrefix}: \"{$this->activity->description}\"";

        // --- Cuerpo completo (full_body) ---
        $fullBody = "Hola,\n\n";
        $fullBody .= "Se ha reportado una calificación para la actividad:\n\n";
        $fullBody .= "• **Actividad:** \"{$this->activity->description}\"\n";
        $fullBody .= "• **Calificada por:** {$this->investigator->name}\n";
        $fullBody .= "• **Progreso Reportado:** {$this->percentage}%\n";
        $fullBody .= "• **Estado:** {$statusText}\n\n";

        if (!empty($this->observations)) {
            $fullBody .= "**Observaciones:** \"{$this->observations}\"\n\n";
        } else {
            $fullBody .= "No se proporcionaron observaciones.\n\n";
        }

        if ($isNotCompleted || $isPartiallyCompleted) {
            $fullBody .= "Por favor, revisa el estado de esta actividad y toma las acciones necesarias.";
        } else {
            $fullBody .= "Puedes revisar los detalles de esta calificación en el sistema.";
        }

        // --- Previsualización del cuerpo (body_preview) ---
        $bodyPreview = "Actividad \"{$this->activity->description}\" fue marcada como {$statusText} por {$this->investigator->name}.";
        if (!empty($this->observations)) {
            $bodyPreview .= " Obs: " . Str::limit($this->observations, 50, '...');
        }
        $bodyPreview = Str::limit($bodyPreview, 150, '...');

        return [
            // Campos esperados por el frontend
            'id' => $this->id, // El ID de la notificación (UUID)
            'type' => 'activity_rating', // Tipo general de notificación de calificación
            'subtype' => $isNotCompleted ? 'not_completed' : ($isPartiallyCompleted ? 'partially_completed' : 'completed'), // Subtipo para lógica más fina en frontend
            'title' => $title,
            'body_preview' => $bodyPreview,
            'full_body' => $fullBody,

            // Datos específicos de la notificación
            'activity_id' => $this->activity->id,
            'activity_name' => $this->activity->description,
            'investigator_id' => $this->investigator->id,
            'investigator_name' => $this->investigator->name,
            'percentage_reported' => $this->percentage,
            'observations_report' => $this->observations,
            'action_url' => '/dashboard/activities/' . $this->activity->id, // URL al detalle de la actividad
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

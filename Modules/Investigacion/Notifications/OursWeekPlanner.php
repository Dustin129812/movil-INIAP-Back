<?php

namespace Modules\Investigacion\Notifications;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class OursWeekPlanner extends Notification
{
    use Queueable;

    protected $entries;
    protected $updater;

    public function __construct($entries, User $updater)
    {
        $this->entries = collect($entries);
        $this->updater = $updater;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        Carbon::setLocale('es');

        $messageLines = [];
        $totalMissingHours = 0;
        $daysWithMissingHours = []; // Para listar los días con déficit

        // Filtrar solo las entradas con horas faltantes y preparar el mensaje
        foreach ($this->entries as $entry) {
            // Asegúrate de que la actividad y el producto estén cargados en el modelo WeekActivity
            // Si $entry es una WeekActivity, necesitarías $entry->activity y $entry->activity->product
            $activity = $entry->activity;
            $product  = $activity->product; // Accede al producto a través de la actividad
            $entryDate = Carbon::parse($entry->date);
            $dayName  = $entryDate->isoFormat('dddd'); // Nombre del día de la semana
            $exactDate = $entryDate->isoFormat('DD/MM/YYYY'); // Fecha exacta en formato DD/MM/YYYY

            $hoursLogged = $entry->estimated_hours;
            $hoursRequired = 8; // Asumiendo 8 horas por día como el requisito
            $missingHours = max(0, $hoursRequired - $hoursLogged);

            if ($missingHours > 0) {
                $totalMissingHours += $missingHours;
                $daysWithMissingHours[] = "{$dayName} {$exactDate} (faltan {$missingHours}h)";

                $productName = $product ? $product->name : 'N/A';
                $activityDescription = $activity->description;

                // Mensaje detallado para cada entrada con horas faltantes
                $messageLines[] = "• El {$dayName} {$exactDate}, en el Producto \"{$productName}\" y Actividad \"{$activityDescription}\", planificaste {$hoursLogged}h, faltando {$missingHours}h para completar la jornada de {$hoursRequired}h.";
            }
        }

        $title = "⚠️ Planificación Semanal con Horas Incompletas";
        $fullMessage = "Estimado(a) {$this->updater->name}, \n\nSe ha detectado un déficit en las horas planificadas para tu semana. Por favor, revisa los siguientes detalles:\n\n";

        if (empty($messageLines)) {
            // Este caso debería ser raro si la notificación se dispara solo por déficit
            $fullMessage = "Estimado(a) {$this->updater->name}, \n\nTu planificación semanal está completa y cumple con el requisito de horas. ¡Buen trabajo!";
            $title = "✅ Planificación Semanal Completa";
        } else {
            $fullMessage .= implode("\n\n", $messageLines); // Separar cada detalle de entrada con doble salto de línea
            $fullMessage .= "\n\nEn total, faltan *{$totalMissingHours} horas* por planificar en la semana. Es crucial que completes tu jornada para asegurar el cumplimiento de tus actividades.";
            $fullMessage .= "\n\nPor favor, ingresa al sistema para ajustar tu planificación semanal lo antes posible.";
        }

        // Construir la previsualización (body_preview)
        $bodyPreview = "Alerta: {$this->updater->name} tiene {$totalMissingHours}h incompletas en su planificación semanal.";
        if (!empty($daysWithMissingHours)) {
            $bodyPreview .= " Días afectados: " . implode(', ', array_slice($daysWithMissingHours, 0, 2)); // Muestra hasta 2 días afectados
            if (count($daysWithMissingHours) > 2) {
                $bodyPreview .= " y más.";
            } else {
                $bodyPreview .= ".";
            }
        }
        $bodyPreview = Str::limit($bodyPreview, 150, '...');


        return [
            'id' => $this->id,
            'type' => 'weekly_planner_incomplete_hours',
            'title' => $title,
            'body_preview' => $bodyPreview,
            'full_body' => $fullMessage,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'missing_hours_details' => $daysWithMissingHours, // Útil para el frontend si necesita un desglose
            'total_missing_hours' => $totalMissingHours,
            'action_url' => '/dashboard/week-planner',
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

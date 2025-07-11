<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RateWeeklyActivityNo extends Notification
{
    use Queueable;

    public $activity;
    public $investigator;
    public $percentage;
    public $observations;

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
    
    $mensaje = $this->percentage == 0
    ? "La actividad '{$this->activity->description}' fue marcada como *No realizada* por {$this->investigator->name}."
    : "La actividad '{$this->activity->description}' fue calificada como *incompleta* ({$this->percentage}%) por {$this->investigator->name}.";


    if ($this->observations) {
    $mensaje .= " Observación: {$this->observations}";
    }


    return [
        'activity_id' => $this->activity->id,
        'activity_name' => $this->activity->description,
        'investigator_id' => $this->investigator->id,
        'investigator_name' => $this->investigator->name,
        'percentage' => $this->percentage,
        'observations' => $this->observations,
        'message' => $mensaje,
    ];
    }
}

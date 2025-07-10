<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User;
use Carbon\Carbon;             // ← importa Carbon

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

    $payloadEntries = [];
    $messageLines   = ["{$this->updater->name} actualizó el planner semanal:"];

    foreach ($this->entries as $entry) {
        $activity = $entry->activity;
        $product  = $activity->product;
        $dayName  = Carbon::parse($entry->date)->isoFormat('dddd');
        $hours    = $entry->estimated_hours;
        $leftover = max(0, 8 - $hours);

        // ——— aquí cambiamos el orden ———
        $line = "- {$dayName}:";

        if ($product) {
            $line .= " Producto: {$product->name},";
        }

        $line .= " Actividad: “{$activity->description}”,";

        $line .= " {$hours}h registradas";

        if ($leftover > 0) {
            $line .= ", faltan {$leftover}h";
        }

        $messageLines[] = $line;

        $payloadEntries[] = [
            'day'            => $dayName,
            'product_id'     => $product?->id,
            'product_name'   => $product?->name,
            'activity_id'    => $activity->id,
            'activity_name'  => $activity->description,
            'hours_logged'   => $hours,
            'hours_missing'  => $leftover,
        ];
    }

    return [
        'entries' => $payloadEntries,
        'message' => implode("\n", $messageLines),
    ];
}

}

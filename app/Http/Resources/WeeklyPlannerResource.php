<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyPlannerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'activities' => $this->activities->map(function ($activity) {
                return [
                    'activity_description' => $activity->description,
                    'product_name' => $activity->product->name ?? null, // Aquí agregamos el nombre del producto
                    'week_activities' => $activity->weekActivities ? $activity->weekActivities->map(function ($weekActivity) {
                        return [
                            'week_description' => $weekActivity->description,
                            'date' => $weekActivity->date,
                            'day_of_week' => \Carbon\Carbon::parse($weekActivity->date)->format('l (d/m/Y)'), // Ej: Monday (22/04/2024)
                            'materials' => json_decode($weekActivity->material),
                        ];
                    }) : [],
                ];
            }),
        ];
    }

    private function formatDayOfWeek($date)
    {
        $daysOfWeek = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $date = new \DateTime($date);
        $dayOfWeek = $daysOfWeek[$date->format('w')]; // 'w' devuelve el día de la semana (0 = domingo, 6 = sábado)
        $formattedDate = $date->format('d/m/Y');
        return "{$dayOfWeek} ({$formattedDate})";
    }
}

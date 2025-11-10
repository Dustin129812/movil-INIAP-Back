<?php

namespace App\Modules\Planificacion\Http\Resources;

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
                    'product_name' => $activity->product ? $activity->product->name : null,
                    'product_id' => $activity->product_id,
                    'activity_description' => $activity->description,
                    'week_activities' => $activity->weekActivities->map(function ($weekActivity) {
                        return [
                            'id' => $weekActivity->id,
                            'week_description' => $weekActivity->description,
                            'date' => $weekActivity->date,
                            'day_of_week' => \Carbon\Carbon::parse($weekActivity->date)->format('l (d/m/Y)'),
                            'materials' => $weekActivity->materials->map(function ($material) {
                                return [
                                    'id' => $material->id,
                                    'name' => $material->name,
                                    'quantity' => $material->pivot->quantity,
                                    'description' => $material->pivot->description,
                                ];
                            }),
                            'status' => $weekActivity->status,
                        ];
                    }),
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

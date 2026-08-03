<?php

namespace Modules\TalentoHumano\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // this->route('entry') carga el modelo ThOvertimeEntry automáticamente
        $entry = $this->route('entry');

        return $entry &&
            $entry->report->driver_id === Auth::id() &&
            $entry->report->status === 'borrador';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => [
                'sometimes', // 'sometimes' = validar solo si está presente
                'required',
                'date',
                // Regla de 2 días: no más antiguo que 2 días atrás
                //'after_or_equal:' . now()->subDays(2)->toDateString(),
                //'before_or_equal:' . now()->toDateString()
            ],
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'activity_type_id' => 'sometimes|required|exists:th_activity_types,id',
            'vehicle_placa' => 'sometimes|required|exists:th_vehicles,placa',
            'observations' => 'nullable|string|max:500',
        ];
    }
}

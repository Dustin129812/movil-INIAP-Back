<?php

namespace Modules\TalentoHumano\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\TalentoHumano\Entities\ThOvertimeReport;

class StoreEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Dejamos que el Controller maneje la lógica de "Estado Borrador"
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'overtime_report_id' => 'required|exists:th_overtime_reports,id',
            'date' => [
                'required',
                'date',
                // Regla de 2 días: no más antiguo que 2 días atrás
                //'after_or_equal:' . now()->subDays(2)->toDateString(),
                //'before_or_equal:' . now()->toDateString()
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'activity_type_id' => 'required|exists:th_activity_types,id',
            'vehicle_placa' => 'required|exists:th_vehicles,placa',
            'observations' => 'nullable|string|max:500',
        ];
    }
}

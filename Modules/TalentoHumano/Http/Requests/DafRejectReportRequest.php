<?php

namespace Modules\TalentoHumano\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\TalentoHumano\Entities\ThOvertimeReport;

class DafRejectReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // El permiso 'th.daf.approve' ya se valida en la ruta.
        // Validamos que el reporte esté en el estado 'pendiente_daf'.
        $report = $this->route('report');

        return $report && $report->status === 'pendiente_daf';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|min:10|max:1000',
        ];
    }

    /**
     * Personalizar mensajes de error.
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'El motivo del rechazo es obligatorio.',
            'rejection_reason.min' => 'El motivo debe tener al menos 10 caracteres.',
        ];
    }
}

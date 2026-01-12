<?php

namespace Modules\TalentoHumano\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\TalentoHumano\Entities\ThOvertimeReport;

class RejectReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // El permiso 'th.supervisor.approve' ya se valida en la ruta.
        // Aquí validamos la lógica de negocio:
        // solo se puede rechazar un reporte que está 'pendiente_supervisor'.
        $report = $this->route('report'); // Carga el modelo ThOvertimeReport

        return $report && $report->status === 'pendiente_supervisor';
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

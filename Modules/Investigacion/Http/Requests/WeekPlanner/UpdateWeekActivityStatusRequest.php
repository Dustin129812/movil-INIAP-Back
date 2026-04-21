<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\WeekActivity;

class UpdateWeekActivityStatusRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user->hasRole('station-director')) {
            return true;
        }

        $weekActivityId = $this->route('activity');

        return WeekActivity::where('id', $weekActivityId)
            ->whereHas('activity.product.group', function ($query) use ($user) {
                $query->where('responsible_id', $user->id);
            })
            ->exists();
    }

    /**
     * Reglas de validación para la petición.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['approved', 'rejected', 'reassigned'])],
        ];
    }

    /**
     * Mensajes de error personalizados (Opcional).
     */
    public function messages(): array
    {
        return [
            'status.in' => 'El estado proporcionado no es válido.',
        ];
    }
}

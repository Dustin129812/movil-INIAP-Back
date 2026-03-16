<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Activity;

class StoreWeeklyPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Para la ruta de semanas pasadas
            'selected_date' => ['sometimes', 'required', 'date_format:Y-m-d'],

            // Validaciones del array principal
            'weeklyPlans' => ['required', 'array'],

            // Validaciones de cada ítem (Uso estricto de Rule::exists)
            'weeklyPlans.*.activityId' => [
                'required',
                Rule::exists(Activity::class, 'id')
            ],
            'weeklyPlans.*.description' => ['required', 'string'],
            'weeklyPlans.*.day' => [
                'required',
                'string',
                Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sábado', 'domingo'])
            ],
            'weeklyPlans.*.observations' => ['nullable', 'string'],

            // Materiales
            'weeklyPlans.*.materials' => ['nullable', 'array'],
            'weeklyPlans.*.materials.*.name' => ['required_with:weeklyPlans.*.materials', 'string'],

            // Apoyos Logísticos
            'weeklyPlans.*.logisticSupports' => ['nullable', 'array'],
            'weeklyPlans.*.logisticSupports.*' => [
                'nullable',
                Rule::exists(User::class, 'id')
            ],
        ];
    }
}

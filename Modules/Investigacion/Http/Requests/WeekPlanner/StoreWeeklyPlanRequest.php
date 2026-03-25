<?php

namespace Modules\Investigacion\Http\Requests\WeekPlanner;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\Product;

class StoreWeeklyPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'selected_date' => ['sometimes', 'required', 'date_format:Y-m-d'],

            'weeklyPlans' => ['required', 'array'],

            'weeklyPlans.*.activityId' => [
                'required',
                Rule::exists(Activity::class, 'id')
            ],
            'weeklyPlans.*.productId' => [
                'required',
                Rule::exists(Product::class, 'id')
            ],

            'weeklyPlans.*.description' => ['required', 'string'],

            'weeklyPlans.*.work_location' => ['required', 'string'],

            'weeklyPlans.*.day' => [
                'required',
                'string',
                Rule::in(['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sábado', 'domingo'])
            ],
            'weeklyPlans.*.observations' => ['nullable', 'string'],
            'weeklyPlans.*.indicators' => ['present', 'array'],

            // Materiales
            'weeklyPlans.*.materials' => ['nullable', 'array'],
            'weeklyPlans.*.materials.*.name' => ['required_with:weeklyPlans.*.materials', 'string'],
            'weeklyPlans.*.materials.*.quantity' => ['required_with:weeklyPlans.*.materials', 'integer', 'min:1'],
            'weeklyPlans.*.materials.*.description' => ['nullable', 'string'],

            // Apoyos Logísticos
            'weeklyPlans.*.logisticSupports' => ['nullable', 'array'],
            'weeklyPlans.*.logisticSupports.*' => [
                'nullable',
                Rule::exists(User::class, 'id')
            ],
        ];
    }
}

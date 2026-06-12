<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Kopia\Entities\Lote;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Province;

class StoreLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid_movil' => ['required', 'uuid', Rule::unique(Lote::class, 'uuid_movil')],
            'nombre_lote' => ['required', 'string', 'max:255'],
            'coordenadas' => ['required', 'array', 'min:3'],
            'ubicacion_manual' => ['nullable', 'string', 'max:255'],
            'tipo_riego' => [
                'nullable',
                'string',
                Rule::in(['gravedad', 'goteo', 'aspersión', 'microaspersión'])
            ],

            'province_id' => ['required', Rule::exists(Province::class, 'id')],
            'canton_id' => ['required', Rule::exists(Canton::class, 'id')],
            'location_id' => ['nullable', Rule::exists(Location::class, 'id')],
        ];
    }
}

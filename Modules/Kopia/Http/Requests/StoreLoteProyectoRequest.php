<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Province;
use Modules\Kopia\Entities\Lote;
use Modules\Kopia\Entities\Proyecto;
use Modules\Kopia\Entities\Variedad;
use App\Models\User;

class StoreLoteProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Validaciones del Lote
            'uuid_movil'       => ['required', 'uuid', Rule::unique(Lote::class, 'uuid_movil')],
            'nombre_lote'      => ['required', 'string', 'max:150'],
            'coordenadas'      => ['required', 'array', 'min:3'],
            'ubicacion_manual' => ['nullable', 'string', 'max:255'],
            'altitud'          => ['nullable', 'numeric'],
            'province_id'      => ['required', Rule::exists(Province::class, 'id')],
            'canton_id'        => ['required', Rule::exists(Canton::class, 'id')],
            'location_id'      => ['nullable', Rule::exists(Location::class, 'id')],

            // Validaciones de los Proyectos Anidados
            'proyectos'                      => ['required', 'array', 'min:1'],
            'proyectos.*.uuid_movil'         => ['required', 'uuid', Rule::unique(Proyecto::class, 'uuid_movil')],
            'proyectos.*.titulo'             => ['required', 'string', 'max:255'],
            'proyectos.*.descripcion'        => ['nullable', 'string'],
            'proyectos.*.tipo_ensayo'        => ['nullable', 'string'],
            'proyectos.*.variedades_ids'     => ['required', 'array', 'min:1'],
            'proyectos.*.variedades_ids.*'   => ['required', Rule::exists(Variedad::class, 'id')],
            'proyectos.*.colaboradores'      => ['nullable', 'array'],
            'proyectos.*.colaboradores.*'    => ['required', Rule::exists(User::class, 'id')],
        ];
    }
}

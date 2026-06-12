<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Investigacion\Entities\Canton;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Province;
use Modules\Kopia\Entities\Variedad;
use App\Models\User;

class SyncKopiaRequest extends FormRequest {
    public function rules(): array {
        return [
            'lotes' => ['required', 'array'],
            'lotes.*.uuid_movil' => ['required', 'uuid'],
            'lotes.*.nombre_lote' => ['required', 'string'],
            'lotes.*.coordenadas' => ['nullable', 'array', 'min:3'],
            'lotes.*.ubicacion_manual' => ['nullable', 'string'],

            'lotes.*.province_id' => ['required', Rule::exists(Province::class, 'id')],
            'lotes.*.canton_id' => ['required', Rule::exists(Canton::class, 'id')],
            'lotes.*.location_id' => ['nullable', Rule::exists(Location::class, 'id')],
            'lotes.*.parroquia' => ['nullable', 'string', 'max:255'],
            'lotes.*.altitud' => ['nullable', 'numeric'],
            'lotes.*.condiciones_terreno' => ['nullable', 'array'],
            'lotes.*.tipo_riego' => [
                'nullable',
                'string',
                Rule::in(['gravedad', 'goteo', 'aspersión', 'microaspersión'])
            ],

            'lotes.*.proyectos' => ['nullable', 'array'],
            'lotes.*.proyectos.*.uuid_movil' => ['required', 'uuid'],
            'lotes.*.proyectos.*.responsable_id' => ['required', Rule::exists(User::class, 'id')],
            'lotes.*.proyectos.*.variedades_ids' => ['required', 'array', 'min:1'],
            'lotes.*.proyectos.*.variedades_ids.*' => ['required', Rule::exists(Variedad::class, 'id')],
            'lotes.*.proyectos.*.titulo' => ['required', 'string'],
            'lotes.*.proyectos.*.descripcion' => ['nullable', 'string'],
            'lotes.*.proyectos.*.tipo_acolchado' => [
                'nullable',
                'string',
                Rule::in(['con_acolchado', 'parcialmente_acolchado', 'sin_acolchado'])
            ],

            'lotes.*.proyectos.*.tipo_ensayo' => ['nullable', 'string', Rule::in(['con_diseno', 'sin_diseno', 'multiplicacion'])],
            'lotes.*.proyectos.*.financiamiento' => ['nullable', 'string', 'max:255'],
            'lotes.*.proyectos.*.colaborador_nombre' => ['nullable', 'string', 'max:255'],
            'lotes.*.proyectos.*.colaborador_celular' => ['nullable', 'string', 'max:20'],

            'lotes.*.proyectos.*.ciclos' => ['nullable', 'array'],
            'lotes.*.proyectos.*.ciclos.*.uuid_movil' => ['required', 'uuid'],
            'lotes.*.proyectos.*.ciclos.*.cultivo_variedad' => ['required', 'string'],
            'lotes.*.proyectos.*.ciclos.*.distancia_siembra' => ['required', 'string'],
            'lotes.*.proyectos.*.ciclos.*.fecha_siembra' => ['required', 'date'],
            'lotes.*.proyectos.*.ciclos.*.metricas_siembra' => ['nullable', 'array'],

            'lotes.*.proyectos.*.ciclos.*.visitas' => ['nullable', 'array'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.uuid_movil' => ['required', 'uuid'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.tecnico_nombre' => ['required', 'string'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.fecha_visita' => ['required', 'date'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.observaciones' => ['nullable', 'string'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.recomendaciones' => ['nullable', 'string'],

            'lotes.*.proyectos.*.ciclos.*.visitas.*.hojas_datos' => ['nullable', 'array'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.hojas_datos.*.uuid_movil' => ['required', 'uuid'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.hojas_datos.*.nombre_plantilla' => ['required', 'string'],
            'lotes.*.proyectos.*.ciclos.*.visitas.*.hojas_datos.*.datos_variables' => ['required', 'array'],
        ];
    }
}

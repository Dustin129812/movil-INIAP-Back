<?php

namespace Modules\Kopia\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'kopia.proyectos';

    protected $fillable = [
        'uuid_movil',
        'lote_id',
        'responsable_id',
        'variedad_id',
        'titulo',
        'descripcion',
        'objetivos',
        'informacion_adicional',
        'tipo_ensayo',
        'financiamiento',
        'colaborador_nombre',
        'colaborador_telefono',
        'colaborador_celular',
    ];

    protected $casts = [
        'objetivos' => 'array',
        'informacion_adicional' => 'array',
        'variedades_ids' => 'array',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function variedad(): BelongsTo
    {
        return $this->belongsTo(Variedad::class, 'variedad_id');
    }

    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kopia.proyecto_colaborador', 'proyecto_id', 'user_id')
            ->withTimestamps();
    }

    public function ciclos(): HasMany
    {
        return $this->hasMany(CicloCultivo::class, 'proyecto_id');
    }

    public function variedades()
    {
        // Declaración estricta: esquema.tabla
        return $this->belongsToMany(Variedad::class, 'kopia.proyecto_variedad');
    }
}

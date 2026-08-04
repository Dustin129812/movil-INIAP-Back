<?php

namespace Modules\AgroDecide\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    protected $table = 'AgroDecide.proyectos';

    protected $fillable = [
        'uuid_movil',
        'lote_id',
        'responsable_id',
        'titulo',
        'descripcion',
        'objetivos',
        'informacion_adicional',
        'variedad',
        'fecha_siembra',
        'tipo_ensayo',
        'tipo_tratamiento',
        'financiamiento',
        'colaborador_nombre',
        'colaborador_telefono',
        'colaborador_celular',
        'tipo_acolchado',
        'dispositivo_invitado_id',
        'estado_verificacion',
    ];

    protected $casts = [
        'fecha_siembra' => 'date',
        'objetivos' => 'array',
        'informacion_adicional' => 'array',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function colaboradores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'AgroDecide.proyecto_colaborador', 'proyecto_id', 'user_id')
            ->withTimestamps();
    }

    public function ciclos(): HasMany
    {
        return $this->hasMany(CicloCultivo::class, 'proyecto_id');
    }
}

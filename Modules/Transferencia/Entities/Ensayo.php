<?php

namespace Modules\Transferencia\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Product;

class Ensayo extends Model
{
    use SoftDeletes;

    protected $table = 'transferencia.ensayos';

    protected $fillable = [
        'nombre',
        'tipo',
        'estado',
        'nombre_tecnologia',
        'tipo_tecnologia',
        'tiene_protocolo',
        'aprobado_por_comite',
        'fecha_aprobacion_protocolo',
        'archivo_protocolo_path',
        'archivo_informe_path',
        'acuerdo_id',
        'producto_id',
        'actividad_id',
        'location_id',
        'user_id',
    ];

    protected $casts = [
        'tiene_protocolo' => 'boolean',
        'aprobado_por_comite' => 'boolean',
        'fecha_aprobacion_protocolo' => 'date',
        'archivo_protocolo_path' => 'array',
        'archivo_informe_path' => 'array',
    ];

    /**
     * Relación con el Producto del POA
     */
    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    /**
     * Relación con la Actividad del POA
     */
    public function actividad()
    {
        return $this->belongsTo(Activity::class, 'actividad_id');
    }

    /**
     * Relación Muchos a Muchos con Usuarios (Equipo Técnico)
     */
    public function equipoTecnico(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'transferencia.ensayo_user',
            'ensayo_id',
            'user_id'
        );
    }

    public function parcelas()
    {
        return $this->hasMany(Parcela::class, 'ensayo_id'); // Asegúrate de que el foreign key sea el correcto si no es 'ensayo_id'
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}

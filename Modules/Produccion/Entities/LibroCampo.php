<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class LibroCampo extends Model
{
    protected $table = 'produccion.libros_campo';
    protected $fillable =
        [
            'lote_id',
            'codigo',
            'qr_token',
            'nombre',
            'fecha_inicio',
            'fecha_fin',
            'estado',
            'cantidad_cosechada',
            'insumo_cosechado_id',
            'kardex_ingreso_id'
        ];

    public function lote()
    {
        return $this->belongsTo(Lote::class);

    }
    public function actividades()
    {
        return $this->hasMany(Actividad::class);
    }

    public function actividadesPersonal()
    {
        return $this->hasMany(ActividadPersonal::class);
    }

    public function actividadesMaquinaria()
    {
        return $this->hasMany(ActividadMaquinaria::class, 'libro_campo_id');
    }

    public function registrosClimaticos()
    {
        return $this->hasMany(RegistroClimatico::class);
    }
}

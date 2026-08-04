<?php
namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ActividadPersonal extends Model
{
    protected $table = 'produccion.actividades_personal';
    protected $fillable =
        [
            'libro_campo_id',
            'user_id',
            'fecha',
            'labor',
            'horas_trabajadas',
            'costo_hora',
            'costo_total'
        ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

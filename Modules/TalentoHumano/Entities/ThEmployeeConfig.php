<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User; // Asegúrate que esta ruta a tu modelo User sea correcta

class ThEmployeeConfig extends Model
{
    use HasFactory;

    protected $table = 'th_employee_configs';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'rmu',
    ];

    /**
     * Obtiene el usuario (conductor) al que pertenece esta configuración.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

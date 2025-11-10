<?php

namespace App\Modules\Planificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyPulse extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'week_start_date',
        'status',
        'comment',
    ];

    /**
     * Obtiene el usuario al que pertenece este pulso.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogisticSupport extends Model
{
    use HasFactory;
    protected $table = 'logistic_support'; 

    protected $fillable = [
        'name',
    ];

    // Relación inversa para WeekActivity
    public function weekActivities()
    {
        return $this->belongsToMany(
            related: WeekActivity::class,
            table: 'weekly_logistic', // ¡Nuevo nombre de la tabla pivote!
            foreignPivotKey: 'logistic_support_id', // Clave foránea de LogisticSupport en la tabla pivote
            relatedPivotKey: 'weekly_activities_id' // Clave foránea de WeekActivity en la tabla pivote
        )
        ->withTimestamps()
        ->withPivot('deleted_at'); // ¡Importante! Para manejar Soft Deletes en la tabla pivote
    }
}

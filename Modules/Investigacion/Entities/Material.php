<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'materials';
    protected $fillable = [
        'name',
    ];

    public function weeklyActivities()
    {
        return $this->belongsToMany(WeekActivity::class, 'material_week_activity')
            ->withPivot('quantity') // Para traer también la cantidad
            ->withTimestamps(); // Opcional, si tu pivote tiene timestamps
    }
}



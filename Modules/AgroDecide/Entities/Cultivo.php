<?php

namespace Modules\AgroDecide\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cultivo extends Model
{
    protected $table = 'AgroDecide.cultivos';

    protected $fillable = [
        'nombre',
        'nombre_cientifico'
    ];

    public function variedades(): HasMany
    {
        return $this->hasMany(Variedad::class, 'cultivo_id');
    }
}

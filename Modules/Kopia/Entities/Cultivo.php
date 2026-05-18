<?php

namespace Modules\Kopia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cultivo extends Model
{
    protected $table = 'kopia.cultivos';

    protected $fillable = [
        'nombre',
        'nombre_cientifico'
    ];

    public function variedades(): HasMany
    {
        return $this->hasMany(Variedad::class, 'cultivo_id');
    }
}

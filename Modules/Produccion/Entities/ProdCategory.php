<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class ProdCategory extends Model
{
    protected $fillable = [
        'name',
        'type'
    ];

    public function varieties()
    {
        return $this->hasMany(ProdVariety::class);
    }
}

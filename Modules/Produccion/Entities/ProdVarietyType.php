<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class ProdVarietyType extends Model
{

    protected $table = 'variety_types';
    protected $fillable = ['name'];

    public function varieties()
    {
        return $this->hasMany(ProdVariety::class);
    }
}

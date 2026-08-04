<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResearchArea extends Model
{
    use  SoftDeletes;

    protected $fillable = ['name'];

    public function lines()
    {
        return $this->hasMany(ResearchLine::class);
    }
}

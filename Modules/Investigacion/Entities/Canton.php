<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Canton extends Model
{
    use SoftDeletes;
    protected $table = 'cantons';
    protected $fillable = [
        'name',
        'codigo_inec',
        'provincia_id',
    ];

    public function locations(){
        return $this->hasMany(Location::class);
    }
}

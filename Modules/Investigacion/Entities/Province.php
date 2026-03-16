<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use SoftDeletes;
    protected $table = 'provinces';
    protected $fillable = [
        'name',
        'codigo_inec',
    ];

    public function locations(){
        return $this->hasMany(Location::class);
    }
}

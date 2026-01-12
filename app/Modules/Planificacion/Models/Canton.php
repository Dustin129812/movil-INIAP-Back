<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Canton extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cantons';
    protected $fillable = [
        'name',
    ];

    public function locations(){
        return $this->hasMany(Location::class);
    }
}

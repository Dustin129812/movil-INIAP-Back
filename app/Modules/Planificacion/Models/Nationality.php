<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nationality extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'nationalities';
    protected $fillable = [
        'name',
    ];

    public function nationalities(){
        return $this->hasMany(Nationality::class);
    }
}

<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ethnic_Group extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'ethnic_groups';
    protected $fillable = [
        'name',
    ];

    public function ethnic_groups(){
        return $this->hasMany(Ethnic_Group::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ethnic_Group extends Model
{
    use HasFactory;
    protected $table = 'ethnic_groups';
    protected $fillable = [
        'name',
    ];

    public function ethnic_groups(){
        return $this->hasMany(Ethnic_Group::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Multidisciplinary_Group extends Model
{
    use HasFactory;
    protected $table = 'multidisciplinary_groups';
    protected $fillable = [
        'name',
        'location_id',
        'rubro_id'
    ];

    public function multidisciplinary_groups(){
        return $this->hasMany(User::class);
    }

    public function rubros(){
        return $this->belongsTo(Rubro::class);
    }
}

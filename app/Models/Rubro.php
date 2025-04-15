<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    use HasFactory;
    protected $table = 'rubros';
    protected $fillable = [
        'name'
    ];

    public function multidisciplinary_group(){
        return $this->hasMany(Multidisciplinary_Group::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }
}

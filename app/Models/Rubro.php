<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubro extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'rubros';
    protected $fillable = [
        'name'
    ];

    public function product(){
        return $this->hasMany(Product::class);
    }

    public function products(){
        return $this->hasMany(Product::class);
    }

    public function groups(){
        return $this->hasMany(Group::class);
    }
}

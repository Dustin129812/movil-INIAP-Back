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

    public function product(){
        return $this->hasMany(Product::class);
    }
}

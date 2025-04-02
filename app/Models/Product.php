<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'name',
        'budget',
        'user_id',
        'rubro_id'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function rubro(){
        return $this->belongsTo(Rubro::class);
    }

    public function activity(){
        return $this->hasMany(Activity::class);
    }
}

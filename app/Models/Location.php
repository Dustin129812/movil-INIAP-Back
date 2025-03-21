<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $table = 'locations';
    protected $fillable = [
        'name',
        'adress',
        'province_id',
        'canton_id',
    ];

    public function locations(){
        return $this->hasMany(User::class);
    }

    public function provinces(){
        return $this->belongsTo(Province::class);
    }

    public function cantons(){
        return $this->belongsTo(Canton::class);
    }

    public function pei(){
        return $this->hasMany(Pei::class);
    }
}

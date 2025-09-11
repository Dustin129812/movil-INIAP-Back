<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;
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

    public function canton(){
        return $this->belongsTo(Canton::class);
    }

    public function product(){
        return $this->hasMany(Product::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

}

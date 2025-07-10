<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Objetive extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'objetives';
    protected $fillable = [
        'name',
    ];

    public function pei(){
        return $this->hasMany(Pei::class);
    }

    public function activity(){
        return $this->hasMany(Activity::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Objetive extends Model
{
    use HasFactory;
    protected $table = 'objetives';
    protected $fillable = [
        'name',
        'activity_id'
    ];

    public function pei(){
        return $this->hasMany(Pei::class);
    }
}

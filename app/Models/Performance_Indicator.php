<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Performance_Indicator extends Model
{
    use HasFactory;
    protected $table = 'performance_indicators';
    protected $fillable = [
        'name'
    ];

    public function pei(){
        return $this->hasMany(Pei::class);
    }
}

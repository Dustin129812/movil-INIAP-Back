<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Crops extends Model
{
    use SoftDeletes;
    protected $table = 'crops';
    protected $fillable = [
        'name',
        'productive_rubro_id',
    ];

    public function productive_rubro(){
        return $this->belongsTo(Productive_Rubro::class,'productive_rubro_id');
    }

    public function productiveRubro(){
        return $this->belongsTo(Productive_Rubro::class,'productive_rubro_id');
    }
}

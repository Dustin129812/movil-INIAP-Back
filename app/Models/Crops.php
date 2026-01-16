<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Productive_Rubro;

class Crops extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'crops';
    protected $fillable = [
        'name',
        'productive_rubro_id',
    ];

    public function productive_rubro(){
        return $this->belongsTo(Productive_Rubro::class,'productive_rubro_id');
    }
}

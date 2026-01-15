<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productive_Rubro extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'productive_rubros';
    protected $fillable = [
        'name',
        'location_id'
    ];

    public function location(){
        return $this->belongsTo(Location::class);
    }
}

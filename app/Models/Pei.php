<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pei extends Model
{
    use HasFactory;
    protected $table = 'pei';
    protected $fillable = [
        'name',
        'locations_id',
        'rubro_id',
        'user_id',
        'investigation_area_id',
        'investigation_line_id',
        'objetive_id',
        'performance_indicator_id',
    ];

    public function locations(){
        return $this->belongsTo(Location::class);
    }

    public function rubros(){
        return $this->belongsTo(Rubro::class);
    }

    public function users(){
        return $this->belongsTo(User::class);
    }

    public function investigation_area(){
        return $this->belongsTo(Investigation_Area::class);
    }

    public function investigation_line(){
        return $this->belongsTo(Investigation_Line::class);
    }

    public function objetive(){
        return $this->belongsTo(Objetive::class);
    }

    public function performance_indicator(){
        return $this->belongsTo(Performance_Indicator::class);
    }
}

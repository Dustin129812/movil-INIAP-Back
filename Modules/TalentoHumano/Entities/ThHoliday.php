<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThHoliday extends Model
{
    use HasFactory;

    protected $table = 'th_holidays';
    protected $primaryKey = 'date';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'date',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}

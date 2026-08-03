<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThSetting extends Model
{
    use HasFactory;

    protected $table = 'th_settings';

    protected $fillable = [
        'key',
        'value',
        'description'
    ];
}

<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class Management extends Model
{
    protected $table = 'th_managements';
    protected $fillable = ['name'];
}

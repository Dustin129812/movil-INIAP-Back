<?php

namespace Modules\TalentoHumano\Entities;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $table = 'th_processes';
    protected $fillable = ['name'];
}

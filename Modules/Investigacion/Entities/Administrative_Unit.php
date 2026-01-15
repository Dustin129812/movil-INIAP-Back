<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Administrative_Unit extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'administrative_units';
    protected $fillable = [
        'name',
    ];
}

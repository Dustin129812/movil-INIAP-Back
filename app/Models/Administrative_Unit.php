<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrative_Unit extends Model
{
    use HasFactory;
    protected $table = 'administrative_units';
    protected $fillable = [
        'name',
    ];
}

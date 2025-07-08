<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investigation_Area extends Model
{
    use hasFactory, SoftDeletes;
    protected $table = 'investigation_areas';
    protected $fillable = [
        'name',
    ];
}

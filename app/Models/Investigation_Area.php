<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investigation_Area extends Model
{
    use hasFactory;
    protected $table = 'investigation_areas';
    protected $fillable = [
        'name',
    ];
}

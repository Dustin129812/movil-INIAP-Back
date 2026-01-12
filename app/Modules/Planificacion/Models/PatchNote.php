<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatchNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'content',
        'release_date',
        'is_published',
    ];

    protected $casts = [
        'release_date' => 'date',
        'is_published' => 'boolean',
    ];
}

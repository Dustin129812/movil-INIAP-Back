<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'group',
            'name',
            'code',
            'description',
            'is_active'
        ];
}

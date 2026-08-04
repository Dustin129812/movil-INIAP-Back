<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget_Type extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'budget_types';
    protected $fillable = [
        'name',
    ];
}

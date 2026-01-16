<?php

namespace Modules\Investigacion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'positions';
    protected $fillable = [
        'name',
    ];

    public function positions()
    {
        return $this->hasMany(User::class);
    }
}

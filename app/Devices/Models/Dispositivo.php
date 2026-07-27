<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'modelo',
        'sistema_operativo',
        'ultimo_login'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investigation_Line extends Model
{
    use HasFactory;
    protected $table = 'investigation_lines';
    protected $fillable = [
        'name'
    ];

    public function pei(){
        return $this->hasMany(Pei::class);
    }
}

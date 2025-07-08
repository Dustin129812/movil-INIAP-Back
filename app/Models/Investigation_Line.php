<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investigation_Line extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'investigation_lines';
    protected $fillable = [
        'name'
    ];

    public function pei(){
        return $this->hasMany(Pei::class);
    }
}

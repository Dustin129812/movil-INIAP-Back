<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Multidisciplinary_Group extends Model
{
    use HasFactory;
    protected $table = 'multidisciplinary_groups';
    protected $fillable = [
        'name',
        'leader_id',
        'location_id',
        'rubro_id'
    ];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id');
    }

    public function pei(){
        return $this->hasMany(Pei::class);
    }

}

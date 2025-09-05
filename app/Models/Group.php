<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'name',
            'rubro_id',
            'location_id',
            'creator_id',
            'responsible_id',
        ];

    public function rubro() {
        return $this->belongsTo(Rubro::class);
    }

    public function location() {
        return $this->belongsTo(Location::class);
    }

    // El creador del grupo
    public function creator() {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // Los miembros del grupo
    public function members() {
        return $this->belongsToMany(User::class, 'group_user');
    }

    public function responsible() {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}

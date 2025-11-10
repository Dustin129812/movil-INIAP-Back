<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable =
        [
            'title',
            'description',
            'type',
            'start_date',
            'end_date',
            'is_active'
        ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }
}


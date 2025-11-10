<?php

namespace App\Modules\Planificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    protected $fillable =
        [
            'survey_id',
            'user_id',
            'session_id'
        ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}


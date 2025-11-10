<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable =
        [
            'response_id',
            'question_id',
            'value'
        ];

    public function response()
    {
        return $this->belongsTo(Response::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}


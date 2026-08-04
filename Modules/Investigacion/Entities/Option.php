<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable =
        [
            'question_id',
            'text',
            'order'
        ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

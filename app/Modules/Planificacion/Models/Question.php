<?php

namespace App\Modules\Planificacion\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable =
        [
            'survey_id',
            'text',
            'type',
            'is_required',
            'order'
        ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}

<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResearchLine extends Model
{
    use SoftDeletes;

    protected $fillable = ['research_area_id', 'name'];

    public function area()
    {
        return $this->belongsTo(ResearchArea::class, 'research_area_id');
    }
}

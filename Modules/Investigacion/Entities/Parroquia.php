<?php

namespace Modules\Investigacion\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parroquia extends Model
{
    use SoftDeletes;

    protected $table = 'parroquias';
    protected $fillable = ['canton_id', 'codigo_inec', 'nombre', 'canton_id'];

    public function canton(): BelongsTo
    {
        return $this->belongsTo(Canton::class);
    }
}

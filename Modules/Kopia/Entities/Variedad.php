<?php


namespace Modules\Kopia\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variedad extends Model
{
    protected $table = 'kopia.variedades';

    protected $fillable = [
        'cultivo_id',
        'nombre',
        'caracteristicas_base'
    ];

    protected $casts = [
        'caracteristicas_base' => 'array',
    ];

    public function cultivo(): BelongsTo
    {
        return $this->belongsTo(Cultivo::class, 'cultivo_id');
    }
}

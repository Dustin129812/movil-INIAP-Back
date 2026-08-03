<?php

namespace Modules\Kopia\Entities;

use Illuminate\Database\Eloquent\Model;

class DispositivoInvitado extends Model
{
    protected $table = 'kopia.dispositivos_invitados';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'modelo_dispositivo',
        'estado',
    ];
}

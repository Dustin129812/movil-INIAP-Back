<?php

namespace Modules\AgroDecide\Entities;

use Illuminate\Database\Eloquent\Model;

class DispositivoInvitado extends Model
{
    protected $table = 'AgroDecide.dispositivos_invitados';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'modelo_dispositivo',
        'sistema_operativo',
        'hardware',
        'estado',
        'ultimo_login',
    ];

    protected $casts = [
        'ultimo_login' => 'datetime',
    ];
}

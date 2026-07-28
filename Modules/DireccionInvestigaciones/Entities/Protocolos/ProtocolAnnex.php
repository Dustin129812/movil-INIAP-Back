<?php

namespace Modules\DireccionInvestigaciones\Entities\Protocolos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolAnnex extends Model
{
    protected $table = 'investigaciones.protocol_annexes';
    protected $guarded = ['id'];

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(IdiProtocol::class, 'protocol_id');
    }
}

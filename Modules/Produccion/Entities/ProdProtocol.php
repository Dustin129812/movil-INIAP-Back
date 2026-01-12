<?php

namespace Modules\Produccion\Entities;

use Illuminate\Database\Eloquent\Model;

class ProdProtocol extends Model
{
    protected $table = 'prod_protocols';
    protected $guarded = [];

    public function variety()
    {
        return $this->belongsTo(ProdVariety::class, 'variety_id');
    }

    public function details()
    {
        return $this->hasMany(ProtocolDetail::class, 'protocol_id');
    }
}

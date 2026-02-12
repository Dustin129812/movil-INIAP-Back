<?php
namespace Modules\Investigacion\Entities;
use Illuminate\Database\Eloquent\Model;

class ProtocolAnnex extends Model
{
    protected $fillable = [
        'protocol_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size'
    ];
}

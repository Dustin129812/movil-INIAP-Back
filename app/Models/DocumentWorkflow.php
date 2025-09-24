<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentWorkflow extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Define las propiedades que deben ser convertidas a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'read_at' => 'datetime',
        'action_at' => 'datetime',
        'document_id',
        'sender_id',
        'recipient_id',
        'recipient_type',
        'signature_data',
        'action_type',
        'status',
        'comments',
        'step',
        'sender_id',
        'recipient_id',
        'reassigned_to_id',
        'state'
        ];

    /**
     * Obtiene el documento al que pertenece este flujo de trabajo.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Obtiene el usuario que envió (remitente) en este flujo de trabajo.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Obtiene el usuario que recibió (destinatario) en este flujo de trabajo.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function reassignedToUser()
    {
        return $this->belongsTo(User::class, 'reassigned_to_id');
    }
}

<?php

// app/Models/Message.php
namespace Modules\Investigacion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable =
        [
            'conversation_id',
            'content',
            'sender_id',
            'guest_id',
            'message_type',
            'file_path',
        ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

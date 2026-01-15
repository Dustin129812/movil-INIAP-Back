<?php

namespace Modules\Investigacion\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable =
        [
            'user_id',
            'guest_id',
            'admin_id',
            'status'
        ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithLastMessage($query)
    {
        $query->addSelect(['last_message_id' => Message::select('id')
            ->whereColumn('conversation_id', 'conversations.id')
            ->latest()
            ->take(1)
        ])->with('lastMessage');
    }

    public function scopeOrderByLastMessage($query)
    {
        $query->orderByDesc(Message::select('created_at')
            ->whereColumn('conversation_id', 'conversations.id')
            ->latest()
            ->take(1)
        );
    }

    public function lastMessage()
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class)->withPivot('last_read_at')->withTimestamps();
    }
}

<?php

namespace App\Events;

use App\Modules\Planificacion\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }

    public function broadcastOn(): array
    {
        // Un canal privado para que solo los admins autenticados lo reciban
        return [new PrivateChannel('admin-notifications')];
    }

    public function broadcastAs()
    {
        // Un nombre claro para el evento en el frontend
        return 'ConversationCreated';
    }
}

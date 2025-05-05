<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class RoleUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $roles; // O podrías enviar solo los IDs de los roles
    public function __construct(User $user, array $roles)
    {
        $this->user = $user;
        $this->roles = $roles;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('users.' . $this->user->id); // Canal privado para el usuario específico
        // O un canal general si quieres notificar a todos los usuarios conectados
        // return new Channel('user.roles.updated');
    }

    public function broadcastAs()
    {
        return 'user.roles.updated';
    }

    public function broadcastWith()
    {
        return ['user_id' => $this->user->id, 'roles' => $this->roles];
    }
}

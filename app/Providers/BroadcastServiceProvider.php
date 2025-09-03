<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['jwt.auth']]);

        Broadcast::channel('conversation.{id}', function ($user, $id) {
            $conversation = \App\Models\Conversation::findOrFail($id);
            return $user || $conversation->guest_id === session('guest_id');
        });
    }
}

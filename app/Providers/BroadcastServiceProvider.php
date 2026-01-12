<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::prefix('api')->middleware(['jwt.auth'])->group(function () {
            Broadcast::routes();
        });

        Broadcast::channel('admin-notifications', function ($user) {
            return $user->hasRole('administrador');
        });

        require base_path('routes/channels.php');
    }
}

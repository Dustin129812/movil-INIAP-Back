<?php
// app/Providers/PulseServiceProvider.php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\PulseApplicationServiceProvider;
use Illuminate\Support\Facades\Auth; // Asegúrate de que este 'use' esté presente

class PulseServiceProvider extends PulseApplicationServiceProvider
{
    // ... (otros métodos)

    /**
     * Register the Pulse authorization services.
     */
    protected function auth(): void
    {
        // --- MODIFICA ESTA PARTE ---
        // Esta línea le da acceso a cualquier usuario que tenga el rol 'administrador'.
        // El 'Auth::user()' se refiere a la persona que está intentando ver la página.
        Gate::define('viewPulse', function ($user) {
            return $user->hasRole('administrador');
        });
    }
}

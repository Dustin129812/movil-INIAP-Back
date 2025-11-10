<?php

namespace App\Modules\TalentoHumano\Shared\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehiculoController extends Controller
{
    /**
     * Devuelve solo los vehículos (placas) asignados
     * al conductor actualmente autenticado.
     * * Endpoint: GET /api/v1/th/horas-extras/vehiculos
     * (Según definimos en routes/api_th.php)
     */
    public function getMisVehiculos()
    {
        $user = Auth::user();

        // Carga los vehículos (placas) desde la relación 'vehiculos'
        // que definimos en el modelo User.
        $vehiculos = $user->vehiculos()->get(['vehiculos.id', 'placa', 'descripcion']);

        return response()->json($vehiculos);
    }

    // --- NOTA ---
    // Más adelante, aquí podemos añadir métodos para el Admin (TH Admin)
    // como crearVehiculo(), asignarVehiculoAConductor(), etc.
    // Por ahora, solo necesitamos la función de lectura para el conductor.
}

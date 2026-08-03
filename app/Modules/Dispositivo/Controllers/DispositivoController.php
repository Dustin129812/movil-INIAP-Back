<?php

namespace App\Modules\Dispositivo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Dispositivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispositivoController extends Controller
{
    public function index(): JsonResponse
    {
        $dispositivos = Dispositivo::with('user')->get();
        return response()->json([
            'success' => true,
            'data' => $dispositivos,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $dispositivo = Dispositivo::with('user')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $dispositivo,
        ]);
    }
}

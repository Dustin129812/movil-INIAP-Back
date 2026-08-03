<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoteController extends Controller
{
    private function normalizarLote($lote)
    {
        $data = $lote->toArray();
        $data['provincia'] = $lote->provincia ? [
            'id' => $lote->provincia->id,
            'name' => $lote->provincia->nombre,
        ] : null;
        $data['canton'] = $lote->canton ? [
            'id' => $lote->canton->id,
            'name' => $lote->canton->nombre,
        ] : null;
        $data['estacion'] = $lote->estacion ? [
            'id' => $lote->estacion->id,
            'name' => $lote->estacion->nombre,
        ] : null;
        return $data;
    }

    public function index(): JsonResponse
    {
        $lotes = Lote::with(['provincia', 'canton', 'estacion'])
            ->where('user_id', Auth::guard('api')->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'lotes' => $lotes->map(fn($l) => $this->normalizarLote($l)),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $lote = Lote::with(['provincia', 'canton', 'estacion'])
            ->where('user_id', Auth::guard('api')->id())
            ->find($id);

        if (!$lote) {
            return response()->json([
                'success' => false,
                'message' => 'Lote no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'lote' => $this->normalizarLote($lote),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_lote' => 'required|string|max:255',
            'uuid_movil' => 'nullable|string',
            'sync_status' => 'nullable|string|in:PENDING,SYNCED,DRAFT',
            'coordenadas' => 'nullable|array',
            'ubicacion_manual' => 'nullable|string',
            'provincia_id' => 'nullable|exists:provincias,id',
            'canton_id' => 'nullable|exists:cantones,id',
            'estacion_id' => 'nullable|exists:estaciones,id',
        ]);

        if ($request->has('ubicacion') && is_array($request->ubicacion)) {
            $ubicacion = $request->ubicacion;
            if (isset($ubicacion['provincia']['id'])) {
                $data['provincia_id'] = $ubicacion['provincia']['id'];
            }
            if (isset($ubicacion['canton']['id'])) {
                $data['canton_id'] = $ubicacion['canton']['id'];
            }
            if (isset($ubicacion['estacion']['id'])) {
                $data['estacion_id'] = $ubicacion['estacion']['id'];
            }
        }

        $lote = Lote::create([
            ...$data,
            'user_id' => Auth::guard('api')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lote creado correctamente',
            'lote' => $this->normalizarLote($lote->load(['provincia', 'canton', 'estacion'])),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lote = Lote::where('user_id', Auth::guard('api')->id())->find($id);

        if (!$lote) {
            return response()->json([
                'success' => false,
                'message' => 'Lote no encontrado',
            ], 404);
        }

        $data = $request->validate([
            'nombre_lote' => 'sometimes|required|string|max:255',
            'uuid_movil' => 'nullable|string',
            'sync_status' => 'nullable|string|in:PENDING,SYNCED,DRAFT',
            'coordenadas' => 'nullable|array',
            'ubicacion_manual' => 'nullable|string',
            'provincia_id' => 'nullable|exists:provincias,id',
            'canton_id' => 'nullable|exists:cantones,id',
            'estacion_id' => 'nullable|exists:estaciones,id',
        ]);

        if ($request->has('ubicacion') && is_array($request->ubicacion)) {
            $ubicacion = $request->ubicacion;
            if (isset($ubicacion['provincia']['id'])) {
                $data['provincia_id'] = $ubicacion['provincia']['id'];
            }
            if (isset($ubicacion['canton']['id'])) {
                $data['canton_id'] = $ubicacion['canton']['id'];
            }
            if (isset($ubicacion['estacion']['id'])) {
                $data['estacion_id'] = $ubicacion['estacion']['id'];
            }
        }

        $lote->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Lote actualizado correctamente',
            'lote' => $this->normalizarLote($lote->fresh(['provincia', 'canton', 'estacion'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $lote = Lote::where('user_id', Auth::guard('api')->id())->find($id);

        if (!$lote) {
            return response()->json([
                'success' => false,
                'message' => 'Lote no encontrado',
            ], 404);
        }

        $lote->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lote eliminado correctamente',
        ]);
    }
}

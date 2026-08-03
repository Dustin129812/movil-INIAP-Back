<?php

namespace Modules\AgroDecide\Services;

use Illuminate\Support\Facades\DB;
use Modules\AgroDecide\Entities\Proyecto;

class ProyectoService
{
    public function crearProyecto(array $data, int $responsableId): Proyecto
    {
        return DB::transaction(function () use ($data, $responsableId) {
            $proyecto = Proyecto::create([
                ...$data,
                'responsable_id' => $responsableId
            ]);

            if (!empty($data['colaboradores'])) {
                $proyecto->colaboradores()->sync($data['colaboradores']);
            }

            return $proyecto->load(['lote', 'colaboradores']);
        });
    }

    public function listarParaUsuario(int $userId)
    {
        return Proyecto::with(['lote', 'responsable', 'colaboradores'])
            ->where('responsable_id', $userId)
            ->orWhereHas('colaboradores', fn($q) => $q->where('user_id', $userId))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene el proyecto con todo su árbol genealógico para la bitácora web.
     */
    public function obtenerDetalleCompleto(int $id)
    {
        return Proyecto::with([
            'lote.provincia',
            'responsable',
            'ciclos.visitas.hojasDatos'
        ])->findOrFail($id);
    }
}

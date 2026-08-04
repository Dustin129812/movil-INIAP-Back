<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Modules\Transferencia\Entities\Acuerdo;
use Modules\Transferencia\Entities\Ensayo;
use Modules\Transferencia\Entities\Organizacion;
use Modules\Transferencia\Entities\Parcela;
use Modules\Transferencia\Traits\ScopesByLocation;

class DashboardService
{
    use ScopesByLocation;

    /**
     * Procesa y calcula las métricas bioestadísticas según autoría o alcance administrativo global.
     */
    public function getMetricasGlobales(int|string $ubicacionId, int $userId, bool $canSeeAll, array $filters = []): array
    {
        $ensayoBase = Ensayo::query();
        $parcelaBase = Parcela::query();
        $acuerdoBase = Acuerdo::query();
        $organizacionBase = Organizacion::query();

        if (!$canSeeAll) {
            $ensayoBase->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('equipoTecnico', function ($sq) use ($userId) {
                        $sq->where('users.id', $userId);
                    });
            });

            $parcelaBase->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('ensayo.equipoTecnico', function ($sq) use ($userId) {
                        $sq->where('users.id', $userId);
                    });
            });

            $acuerdoBase->where('user_id', $userId);
            $organizacionBase->where('user_id', $userId);

        } else {
            if (!empty($filters['location_id'])) {
                $ensayoBase->where('location_id', $filters['location_id']);
                $parcelaBase->where('location_id', $filters['location_id']);
                $acuerdoBase->where('location_id', $filters['location_id']);
                $organizacionBase->where('location_id', $filters['location_id']);
            }

            if (!empty($filters['filter_user_id'])) {
                $targetUserId = $filters['filter_user_id'];
                $ensayoBase->where(function ($q) use ($targetUserId) {
                    $q->where('user_id', $targetUserId)->orWhereHas('equipoTecnico', fn($sq) => $sq->where('users.id', $targetUserId));
                });
                $parcelaBase->where(function ($q) use ($targetUserId) {
                    $q->where('user_id', $targetUserId)->orWhereHas('ensayo.equipoTecnico', fn($sq) => $sq->where('users.id', $targetUserId));
                });
                $acuerdoBase->where('user_id', $targetUserId);
                $organizacionBase->where('user_id', $targetUserId);
            }
        }

        if (!empty($filters['provincia_id'])) {
            $parcelaBase->where('provincia_id', $filters['provincia_id']);
            $organizacionBase->where('provincia_id', $filters['provincia_id']);
        }
        if (!empty($filters['canton_id'])) {
            $parcelaBase->where('canton_id', $filters['canton_id']);
            $organizacionBase->where('canton_id', $filters['canton_id']);
        }
        if (!empty($filters['parroquia_id'])) {
            $parcelaBase->where('parroquia_id', $filters['parroquia_id']);
            $organizacionBase->where('parroquia_id', $filters['parroquia_id']);
        }

        $limitePoas = $filters['limit_poas'] ?? 5; // Por defecto 5, pero el frontend puede pedir 10, 20 o 50

        $topPoas = (clone $ensayoBase)
            ->whereNotNull('producto_id')
            ->select('producto_id', DB::raw('count(*) as total_ensayos'))
            ->with(['producto:id,name'])
            ->groupBy('producto_id')
            ->orderByDesc('total_ensayos')
            ->take((int)$limitePoas)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->producto_id,
                    'nombre' => $item->producto ? $item->producto->name : 'Sin POA Vinculado',
                    'total_ensayos' => $item->total_ensayos
                ];
            });

        $kpis = [
            'ensayos_activos' => (clone $ensayoBase)->where('estado', 'Activo')->count(),
            'parcelas_desplegadas' => (clone $parcelaBase)->count(),
            'acuerdos_vigentes' => (clone $acuerdoBase)->count(),
            'impacto_productores' => (clone $organizacionBase)->sum(DB::raw('participantes_hombres + participantes_mujeres')) ?? 0,
        ];

        $topEnsayos = (clone $ensayoBase)
            ->withCount('parcelas')
            ->has('parcelas')
            ->orderByDesc('parcelas_count')
            ->take(5)
            ->get(['id', 'nombre', 'tipo_tecnologia']);

        $estadosParcelas = (clone $parcelaBase)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $huellaTerritorial = (clone $parcelaBase)
            ->select('provincia_id', DB::raw('count(*) as total'))
            ->with(['provincia:id,name'])
            ->groupBy('provincia_id')
            ->orderByDesc('total')
            ->take(4)
            ->get()
            ->map(function ($item) {
                return [
                    'provincia' => $item->provincia ? $item->provincia->name : 'Desconocida',
                    'total' => $item->total
                ];
            });

        $demografia = [
            'hombres' => (clone $organizacionBase)->sum('participantes_hombres') ?? 0,
            'mujeres' => (clone $organizacionBase)->sum('participantes_mujeres') ?? 0,
        ];

        return [
            'kpis' => $kpis,
            'top_ensayos' => $topEnsayos,
            'top_poas' => $topPoas,
            'estados_parcelas' => $estadosParcelas,
            'huella_territorial' => $huellaTerritorial,
            'demografia' => $demografia
        ];
    }

    public function getPoaDetails(int $productoId, int $userId, bool $canSeeAll, array $filters = []): array
    {
        $query = Ensayo::query()
            ->where('producto_id', $productoId)
            ->with([
                'equipoTecnico:id,name',
                'actividad:id,description',
                'parcelas' => function ($q) {
                    $q->select('id', 'ensayo_id', 'estado', 'provincia_id', 'canton_id', 'localidad')
                        ->with(['provincia:id,name', 'canton:id,name']);
                }
            ])
            ->withCount('parcelas');

        if (!$canSeeAll) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhereHas('equipoTecnico', fn($sq) => $sq->where('users.id', $userId));
            });
        } else {
            if (!empty($filters['location_id'])) {
                $query->where('location_id', $filters['location_id']);
            }
            if (!empty($filters['filter_user_id'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('user_id', $filters['filter_user_id'])
                        ->orWhereHas('equipoTecnico', fn($sq) => $sq->where('users.id', $filters['filter_user_id']));
                });
            }
        }

        $ensayos = $query->orderByDesc('created_at')->get();

        return $ensayos->map(function ($ensayo) {
            return [
                'id' => $ensayo->id,
                'nombre' => $ensayo->nombre,
                'estado' => $ensayo->estado,
                'actividad' => $ensayo->actividad->description ?? 'Sin actividad definida',
                'tecnologia' => $ensayo->tipo_tecnologia,
                'equipo' => $ensayo->equipoTecnico->pluck('name')->join(', ') ?: 'Sin asignar',
                'total_parcelas' => $ensayo->parcelas_count,
                'ubicaciones' => $ensayo->parcelas->map(function($p) {
                    return trim(($p->provincia->name ?? '') . ' / ' . ($p->canton->name ?? ''));
                })->filter()->unique()->values()->all(),
                'salud_parcelas' => $ensayo->parcelas->groupBy('estado')->map->count(),
            ];
        })->toArray();
    }
}

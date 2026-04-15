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

    public function getMetricasGlobales(): array
    {
        // 1. KPIs Principales (Impacto Directo)
        $kpis = [
            'ensayos_activos' => $this->applyLocationScope(Ensayo::query())->where('estado', 'Activo')->count(),
            'parcelas_desplegadas' => $this->applyLocationScope(Parcela::query())->count(),
            'acuerdos_vigentes' => $this->applyLocationScope(Acuerdo::query())->count(),
            'impacto_productores' => $this->applyLocationScope(Organizacion::query())->sum(DB::raw('participantes_hombres + participantes_mujeres')),
        ];

        // 2. Dispersión Tecnológica (Top 5 Ensayos con más parcelas replicadas)
        $topEnsayos = $this->applyLocationScope(Ensayo::query())
            ->withCount('parcelas')
            ->has('parcelas')
            ->orderByDesc('parcelas_count')
            ->take(5)
            ->get(['id', 'nombre', 'tipo_tecnologia']);

        // 3. Tasa de Éxito de Campo (Estados de Vida de las Parcelas)
        $estadosParcelas = $this->applyLocationScope(Parcela::query())
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // 4. Huella Territorial (Top Provincias con más parcelas)
        $huellaTerritorial = $this->applyLocationScope(Parcela::query())
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

        // 5. Brecha de Género en Transferencia
        $demografia = [
            'hombres' => $this->applyLocationScope(Organizacion::query())->sum('participantes_hombres'),
            'mujeres' => $this->applyLocationScope(Organizacion::query())->sum('participantes_mujeres'),
        ];

        return [
            'kpis' => $kpis,
            'top_ensayos' => $topEnsayos,
            'estados_parcelas' => $estadosParcelas,
            'huella_territorial' => $huellaTerritorial,
            'demografia' => $demografia
        ];
    }
}

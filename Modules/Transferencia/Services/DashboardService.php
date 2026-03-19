<?php

namespace Modules\Transferencia\Services;

use Illuminate\Support\Facades\DB;
use Modules\Transferencia\Entities\Ensayo;
use Modules\Transferencia\Entities\Parcela;
use Modules\Transferencia\Entities\Organizacion;
use Modules\Transferencia\Entities\Acuerdo;

class DashboardService
{
    public function getMetricasGlobales(): array
    {
        // 1. KPIs Principales (Impacto Directo)
        $kpis = [
            'ensayos_activos' => Ensayo::where('estado', 'Activo')->count(),
            'parcelas_desplegadas' => Parcela::count(),
            'acuerdos_vigentes' => Acuerdo::count(),
            // Suma matemática a nivel de base de datos (0 consumo de RAM en Laravel)
            'impacto_productores' => Organizacion::sum(DB::raw('participantes_hombres + participantes_mujeres')),
        ];

        // 2. Dispersión Tecnológica (Top 5 Ensayos con más parcelas replicadas)
        $topEnsayos = Ensayo::query()
            ->withCount('parcelas')
            ->has('parcelas')
            ->orderByDesc('parcelas_count')
            ->take(5)
            ->get(['id', 'nombre', 'tipo_tecnologia']);

        // 3. Tasa de Éxito de Campo (Estados de Vida de las Parcelas)
        $estadosParcelas = Parcela::query()
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // 4. Huella Territorial (Top Provincias con más parcelas)
        $huellaTerritorial = Parcela::query()
            ->select('provincia_id', DB::raw('count(*) as total'))
            ->with(['provincia:id,name']) // Asumiendo que guardaste el modelo Province
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
            'hombres' => Organizacion::sum('participantes_hombres'),
            'mujeres' => Organizacion::sum('participantes_mujeres'),
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

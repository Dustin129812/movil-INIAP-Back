<?php

namespace Modules\PlanificacionEstrategica\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
// IMPORTANTE: Importamos los modelos del OTRO módulo
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Rubro;

class DashboardController extends Controller
{
    /**
     * Endpoint para el Dashboard Principal de Planificación
     * Muestra el presupuesto y avance agrupado por Dirección/Rubro
     */
    public function getGlobalOverview()
    {
        // Verificar permisos (Lo haremos con Spatie luego, por ahora lógica simple)
        // if (!auth()->user()->hasRole('planificador-estrategico')) abort(403);

        try {
            // Obtener todos los rubros (Direcciones) y sus productos sumados
            $overview = Rubro::with(['products' => function($q) {
                // Solo traemos lo necesario para no matar la memoria
                $q->select('id', 'rubro_id', 'budget', 'ponderacion');
            }])
                ->get()
                ->map(function ($rubro) {
                    $totalBudget = $rubro->products->sum('budget');

                    // Calcular avance ponderado global de esa dirección
                    // (Esta lógica se puede refinar según tus fórmulas exactas)
                    $avgProgress = $rubro->products->avg('ponderacion') ?? 0;

                    return [
                        'id' => $rubro->id,
                        'direction_name' => $rubro->name,
                        'total_products' => $rubro->products->count(),
                        'total_budget' => $totalBudget,
                        'global_progress' => round($avgProgress, 2),
                        'status' => $this->determineStatus($avgProgress)
                    ];
                });

            return response()->json([
                'msg' => ['summary' => 'Éxito', 'detail' => 'Datos estratégicos cargados'],
                'data' => $overview
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function determineStatus($progress) {
        if ($progress >= 90) return 'Exelente';
        if ($progress >= 70) return 'Bueno';
        if ($progress >= 40) return 'Regular';
        return 'Crítico';
    }
}

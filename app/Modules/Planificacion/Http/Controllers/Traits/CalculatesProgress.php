<?php

namespace App\Modules\Planificacion\Http\Controllers\Traits;

trait CalculatesProgress
{
    /**
     * Calcula el progreso ponderado total para una colección de productos.
     *
     * @param \Illuminate\Database\Eloquent\Collection $products
     * @return float
     */
    private function calculateTotalProgress($products): float
    {
        return $products->sum(function ($product) {
            $productAbsoluteWeight = (float) $product->ponderacion / 100;

            $totalProductProgress = $product->activities->sum(function ($activity) use ($productAbsoluteWeight) {
                $activityAbsoluteWeight = $productAbsoluteWeight * ((float) $activity->ponderacion / 100);
                $totalExecutedPercentage = $activity->monthlyExecutionProgress->sum('percentage');

                return $activityAbsoluteWeight * ($totalExecutedPercentage / 100);
            });

            return $totalProductProgress;
        });
    }
}

<?php

namespace Modules\Inventario\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventario\Entities\Batch;

class AlertController extends Controller
{
    public function alerts()
    {
        $today = now();
        $nextMonth = now()->addDays(30);

        // Lotes Vencidos
        $expired = Batch::where('expiration_date', '<', $today)
            ->where('current_quantity', '>', 0)
            ->with('product') // Eager loading para saber el nombre
            ->get();

        // Lotes por Vencer (Próximos 30 días)
        $expiringSoon = Batch::whereBetween('expiration_date', [$today, $nextMonth])
            ->where('current_quantity', '>', 0)
            ->with('product')
            ->get();

        return response()->json([
            'expired_count' => $expired->count(),
            'expiring_soon_count' => $expiringSoon->count(),
            'expired_batches' => $expired,
            'expiring_batches' => $expiringSoon
        ]);
    }
}

<?php

namespace App\Modules\Planificacion\Notifications;

use App\Models\User;
use App\Modules\Planificacion\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// ¡Importa Str!

class CreateWeekPlanner extends Notification
{
    use Queueable;

    public $product;
    public $updater;

    public function __construct(Product $product, User $updater)
    {
        $this->product = $product;
        $this->updater = $updater;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $title = "Nueva Planificación Semanal Creada";
        $fullMessage = "La planificación semanal para el producto '{$this->product->name}' ha sido realizada por {$this->updater->name}. Está en espera de aprobación.";
        $messagePreview = Str::limit($fullMessage, 150, '...');

        return [
            // Campos esperados por el frontend
            'id' => $this->id,
            'type' => 'week_planner_created', // Tipo específico
            'title' => $title,
            'body_preview' => $messagePreview,
            'full_body' => $fullMessage,

            // Datos específicos
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'action_url' => 'weekly-planning', // Ejemplo: URL a la lista de espera
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str; // ¡Importa Str!

class ProductUpdated extends Notification
{
    use Queueable;

    public $product;
    public $updater;

    public function __construct(Product $product, User $updater)
    {
        $this->product = $product;
        $this->updater = $updater;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $title = "Producto Actualizado: {$this->product->name}";
        $fullMessage = "El producto '{$this->product->name}' ha sido actualizado por {$this->updater->name}. Revise los cambios si es necesario.";
        $messagePreview = Str::limit($fullMessage, 150, '...');

        return [
            // Campos esperados por el frontend
            'id' => $this->id,
            'type' => 'product_update', // Tipo específico
            'title' => $title,
            'body_preview' => $messagePreview,
            'full_body' => $fullMessage,

            // Datos específicos
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'action_url' => '/dashboard/products/' . $this->product->id, // Ejemplo: URL al producto
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

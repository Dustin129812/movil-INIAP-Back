<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Product;
use App\Models\User;

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
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'message' => "El producto '{$this->product->name}' ha sido actualizado por {$this->updater->name}.",
        ];
    }
}

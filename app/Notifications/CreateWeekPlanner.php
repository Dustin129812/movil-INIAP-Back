<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'message' => "La planifiacion semanal '{$this->product->name}' ha sido realizada por {$this->updater->name}.",
        ];
    }
}

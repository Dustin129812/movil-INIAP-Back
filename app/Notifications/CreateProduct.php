<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str; // Importa Str para usar Str::limit

class CreateProduct extends Notification
{
    use Queueable;

    public $product;
    public $updater;

    public function __construct(Product $product, User $updater)
    {
        $this->product = $product;
        $this->updater = $updater;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Contenido completo del mensaje
        $fullMessage = "El producto '{$this->product->name}' ha sido asignado a su cargo por {$this->updater->name}.";

        // Previsualización del mensaje (truncado)
        $messagePreview = Str::limit($fullMessage, 150, '...'); // Limita a 150 caracteres para la vista previa

        return [
            // Campos esperados por el frontend
            'id' => $this->id, // Laravel ya lo añade, pero explicitarlo puede ayudar a la claridad
            'type' => 'product_assignment', // Un tipo de notificación que puedes usar para íconos o lógica específica en el frontend
            'title' => "Asignación de Producto: {$this->product->name}", // Título conciso para la lista
            'body_preview' => $messagePreview, // La versión corta del mensaje
            'full_body' => $fullMessage, // El mensaje completo para la vista de detalle

            // Datos específicos de la notificación que podrías necesitar en el detalle o para acciones
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'updater_id' => $this->updater->id,
            'updater_name' => $this->updater->name,
            'action_url' => '/dashboard/products/' . $this->product->id, // Ejemplo de URL para "Ver más detalles"
            'created_at' => now()->toDateTimeString(), // Asegúrate de enviar la fecha de creación
        ];
    }
}

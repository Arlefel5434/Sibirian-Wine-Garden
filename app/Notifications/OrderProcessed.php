<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderProcessed extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order #{$this->order->id} has been processed")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Your order #{$this->order->id} has been paid and accepted for delivery.")
            ->line("Total: {$this->formattedTotal()} {$this->order->currency}")
            ->action('View order history', url('/orders'))
            ->line('We will notify you when the delivery status changes.');
    }

    private function formattedTotal(): string
    {
        return number_format($this->order->total_amount / 100, 2, '.', '');
    }
}

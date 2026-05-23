<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderFulfilled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order  = $this->order;
        $side   = ucfirst($order->side);
        $symbol = $order->asset->symbol;
        $total  = number_format((float) $order->total_amount, 2);
        $price  = number_format((float) $order->price_per_unit, 2);
        $qty    = number_format((float) $order->quantity, 4);

        return (new MailMessage)
            ->subject("Order Filled: {$side} {$qty} {$symbol} @ \${$price}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$side} order has been executed successfully.")
            ->line("**Asset:** {$order->asset->name} ({$symbol})")
            ->line("**Side:** {$side}")
            ->line("**Quantity:** {$qty}")
            ->line("**Fill Price:** \${$price}")
            ->line("**Total:** \${$total}")
            ->line("**Filled At:** " . $order->filled_at?->format('d M Y H:i:s') . ' UTC')
            ->action('View Portfolio', url('/portfolio'))
            ->salutation('Precious Metals Platform');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'order_fulfilled',
            'order_id'       => $this->order->id,
            'asset_symbol'   => $this->order->asset->symbol,
            'side'           => $this->order->side,
            'quantity'       => (float) $this->order->quantity,
            'price_per_unit' => (float) $this->order->price_per_unit,
            'total_amount'   => (float) $this->order->total_amount,
            'filled_at'      => $this->order->filled_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\PriceAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceAlertTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PriceAlert $alert,
        public readonly float $triggeredPrice
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $asset     = $this->alert->asset;
        $condition = $this->alert->condition === 'above' ? 'risen above' : 'fallen below';
        $diff      = abs($this->triggeredPrice - (float) $this->alert->target_price);
        $diffFmt   = number_format($diff, 2);

        return (new MailMessage)
            ->subject("🔔 Price Alert: {$asset->symbol} {$this->alert->condition} \${$this->alert->target_price}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$asset->name} ({$asset->symbol}) has {$condition} your target price.")
            ->line("**Target:** \$" . number_format((float) $this->alert->target_price, 2))
            ->line("**Current Price:** \$" . number_format($this->triggeredPrice, 2) . "  (Δ \${$diffFmt})")
            ->when($this->alert->note, fn ($mail) => $mail->line("**Your note:** {$this->alert->note}"))
            ->action('View Asset', url("/assets/{$asset->symbol}"))
            ->salutation('Precious Metals Platform');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'price_alert',
            'alert_id'        => $this->alert->id,
            'asset_symbol'    => $this->alert->asset->symbol,
            'condition'       => $this->alert->condition,
            'target_price'    => (float) $this->alert->target_price,
            'triggered_price' => $this->triggeredPrice,
            'triggered_at'    => now()->toIso8601String(),
        ];
    }
}

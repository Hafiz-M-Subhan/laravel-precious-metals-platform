<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\SavingsPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SavingsPlanExecuted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SavingsPlan $plan,
        public readonly Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan     = $this->plan;
        $order    = $this->order;
        $symbol   = $plan->asset->symbol;
        $amount   = number_format((float) $plan->amount_per_cycle, 2);
        $qty      = number_format((float) $order->quantity, 6);
        $price    = number_format((float) $order->price_per_unit, 2);
        $total    = number_format((float) $plan->total_invested, 2);
        $avgCost  = number_format((float) $plan->averageCostBasis(), 2);
        $nextExec = $plan->next_execution_at?->format('d M Y');

        return (new MailMessage)
            ->subject("DCA Executed: {$symbol} — \${$amount} invested")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$plan->frequency} savings plan for {$plan->asset->name} ({$symbol}) has been executed.")
            ->line("**Invested This Cycle:** \${$amount}")
            ->line("**Quantity Bought:** {$qty} {$symbol}")
            ->line("**Fill Price:** \${$price}")
            ->line("**Total Invested (All Time):** \${$total}")
            ->line("**Average Cost Basis:** \${$avgCost}")
            ->line("**Next Execution:** {$nextExec}")
            ->action('View Savings Plan', url('/savings-plans/' . $plan->id))
            ->salutation('Precious Metals Platform');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'savings_plan_executed',
            'plan_id'         => $this->plan->id,
            'asset_symbol'    => $this->plan->asset->symbol,
            'amount_invested' => (float) $this->order->total_amount,
            'quantity_bought' => (float) $this->order->quantity,
            'total_invested'  => (float) $this->plan->total_invested,
            'next_execution'  => $this->plan->next_execution_at?->toIso8601String(),
        ];
    }
}

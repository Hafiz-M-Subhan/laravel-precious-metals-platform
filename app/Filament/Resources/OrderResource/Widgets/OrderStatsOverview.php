<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OrderStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $stats = Cache::remember('admin:order_stats', 30, function () {
            return [
                'filled_today'  => Order::filled()->whereDate('filled_at', today())->count(),
                'volume_today'  => Order::filled()->whereDate('filled_at', today())->sum('total_amount'),
                'pending'       => Order::pending()->count(),
                'failed_today'  => Order::where('status', Order::STATUS_FAILED)
                                        ->whereDate('created_at', today())->count(),
            ];
        });

        return [
            Stat::make('Orders Filled Today', number_format($stats['filled_today']))
                ->description('Market orders filled')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Volume Today', '€' . number_format($stats['volume_today'], 0))
                ->description('Total EUR traded')
                ->icon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Pending Queue', number_format($stats['pending']))
                ->description('Awaiting processing')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Failed Today', number_format($stats['failed_today']))
                ->description('Requires investigation')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}

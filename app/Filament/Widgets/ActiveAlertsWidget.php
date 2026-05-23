<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\PriceAlert;
use App\Models\SavingsPlan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ActiveAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('admin:platform_stats', 30, function () {
            return [
                'active_alerts'   => PriceAlert::where('is_active', true)->whereNull('triggered_at')->count(),
                'alerts_fired_today' => PriceAlert::whereDate('triggered_at', today())->count(),
                'active_dca_plans'   => SavingsPlan::where('status', 'active')->count(),
                'total_users'        => User::count(),
                'orders_today'       => Order::where('status', 'filled')->whereDate('filled_at', today())->count(),
                'volume_today'       => Order::where('status', 'filled')
                                            ->whereDate('filled_at', today())
                                            ->sum('total_amount'),
            ];
        });

        return [
            Stat::make('Active Price Alerts', number_format($stats['active_alerts']))
                ->description($stats['alerts_fired_today'] . ' fired today')
                ->color($stats['active_alerts'] > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-bell'),

            Stat::make('Active DCA Plans', number_format($stats['active_dca_plans']))
                ->description('Automated savings running')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Orders Filled Today', number_format($stats['orders_today']))
                ->description('$' . number_format($stats['volume_today'], 2) . ' volume')
                ->color('primary')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Total Users', number_format($stats['total_users']))
                ->color('gray')
                ->icon('heroicon-o-users'),
        ];
    }
}

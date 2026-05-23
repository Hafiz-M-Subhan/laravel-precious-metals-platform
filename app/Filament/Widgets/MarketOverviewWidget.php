<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MarketOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Asset::query()->active()->orderBy('symbol'))
            ->poll('5s')
            ->columns([
                TextColumn::make('symbol')->sortable()->weight('bold'),
                TextColumn::make('name')->sortable(),
                TextColumn::make('spot_price')
                    ->label('Spot')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('bid_price')->label('Bid')->money('USD'),
                TextColumn::make('ask_price')->label('Ask')->money('USD'),
                BadgeColumn::make('daily_change_pct')
                    ->label('24h %')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . '%')
                    ->colors([
                        'success' => fn ($state) => (float) $state > 0,
                        'danger'  => fn ($state) => (float) $state < 0,
                        'gray'    => fn ($state) => (float) $state === 0.0,
                    ])
                    ->sortable(),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->heading('Live Market Prices')
            ->description('Auto-refreshes every 5 seconds from Redis cache');
    }
}

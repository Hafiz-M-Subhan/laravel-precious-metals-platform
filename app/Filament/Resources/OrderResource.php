<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\Widgets\OrderStatsOverview;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Trading';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Orders are read-only in the admin panel
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('asset.symbol')->badge(),
                Tables\Columns\BadgeColumn::make('side')
                    ->colors([
                        'success' => Order::SIDE_BUY,
                        'danger'  => Order::SIDE_SELL,
                    ]),
                Tables\Columns\TextColumn::make('quantity')
                    ->formatStateUsing(fn ($state, $record) => "{$state} {$record->asset->unit}"),
                Tables\Columns\TextColumn::make('price_per_unit')->money('eur'),
                Tables\Columns\TextColumn::make('total_amount')->money('eur')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => Order::STATUS_PENDING,
                        'primary' => Order::STATUS_PROCESSING,
                        'success' => Order::STATUS_FILLED,
                        'danger'  => [Order::STATUS_CANCELLED, Order::STATUS_FAILED],
                    ]),
                Tables\Columns\TextColumn::make('filled_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Order::STATUS_PENDING    => 'Pending',
                        Order::STATUS_PROCESSING => 'Processing',
                        Order::STATUS_FILLED     => 'Filled',
                        Order::STATUS_CANCELLED  => 'Cancelled',
                        Order::STATUS_FAILED     => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('side')
                    ->options([Order::SIDE_BUY => 'Buy', Order::SIDE_SELL => 'Sell']),
                Tables\Filters\SelectFilter::make('asset')
                    ->relationship('asset', 'symbol'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getWidgets(): array
    {
        return [OrderStatsOverview::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }
}

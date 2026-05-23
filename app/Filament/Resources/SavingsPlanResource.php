<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SavingsPlanResource\Pages;
use App\Models\SavingsPlan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SavingsPlanResource extends Resource
{
    protected static ?string $model = SavingsPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Trading';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('asset.symbol')->badge(),
                Tables\Columns\TextColumn::make('amount_per_cycle')->money('eur'),
                Tables\Columns\TextColumn::make('frequency')->badge(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => SavingsPlan::STATUS_ACTIVE,
                        'warning' => SavingsPlan::STATUS_PAUSED,
                        'danger'  => SavingsPlan::STATUS_CANCELLED,
                    ]),
                Tables\Columns\TextColumn::make('total_invested')->money('eur')->sortable(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->formatStateUsing(fn ($state) => number_format($state, 4) . ' oz'),
                Tables\Columns\TextColumn::make('next_execution_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SavingsPlan::STATUS_ACTIVE    => 'Active',
                        SavingsPlan::STATUS_PAUSED    => 'Paused',
                        SavingsPlan::STATUS_CANCELLED => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('asset')->relationship('asset', 'symbol'),
                Tables\Filters\SelectFilter::make('frequency')
                    ->options([
                        SavingsPlan::FREQUENCY_MONTHLY  => 'Monthly',
                        SavingsPlan::FREQUENCY_BIWEEKLY => 'Biweekly',
                        SavingsPlan::FREQUENCY_WEEKLY   => 'Weekly',
                    ]),
            ])
            ->defaultSort('next_execution_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingsPlans::route('/'),
        ];
    }
}

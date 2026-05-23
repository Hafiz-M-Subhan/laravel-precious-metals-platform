<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceAlertResource\Pages;
use App\Models\PriceAlert;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceAlertResource extends Resource
{
    protected static ?string $model = PriceAlert::class;
    protected static ?string $navigationIcon  = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Trading';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->searchable()->sortable()->label('User'),
                TextColumn::make('asset.symbol')->searchable()->sortable()->label('Asset'),
                BadgeColumn::make('condition')
                    ->colors([
                        'success' => 'above',
                        'danger'  => 'below',
                    ]),
                TextColumn::make('target_price')
                    ->money('USD')
                    ->sortable(),
                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Triggered')
                    ->colors([
                        'success' => fn ($state) => $state,
                        'gray'    => fn ($state) => ! $state,
                    ]),
                TextColumn::make('triggered_at')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                Filter::make('active_only')
                    ->label('Active alerts only')
                    ->query(fn (Builder $q) => $q->where('is_active', true)->whereNull('triggered_at')),
                SelectFilter::make('condition')
                    ->options([
                        'above' => 'Above target',
                        'below' => 'Below target',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false; // Alerts are created via API only
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceAlerts::route('/'),
        ];
    }
}

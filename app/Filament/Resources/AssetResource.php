<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AssetResource\Pages;
use App\Models\Asset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Market';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')->schema([
                Forms\Components\TextInput::make('symbol')
                    ->required()
                    ->maxLength(10)
                    ->placeholder('XAU')
                    ->helperText('ISO 4217 metal code'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('unit')
                    ->required()
                    ->options(['troy_oz' => 'Troy Ounce', 'gram' => 'Gram', 'kg' => 'Kilogram']),
                Forms\Components\Select::make('currency')
                    ->required()
                    ->options(['EUR' => 'Euro', 'USD' => 'US Dollar']),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Current Pricing')->schema([
                Forms\Components\TextInput::make('spot_price')->numeric()->readOnly(),
                Forms\Components\TextInput::make('bid_price')->numeric()->readOnly(),
                Forms\Components\TextInput::make('ask_price')->numeric()->readOnly(),
                Forms\Components\TextInput::make('spread')->numeric()->readOnly(),
                Forms\Components\TextInput::make('daily_change')->numeric()->readOnly(),
                Forms\Components\TextInput::make('daily_change_pct')->numeric()->readOnly()->suffix('%'),
            ])->columns(3)
             ->description('Prices are updated by the live feed — manual edits are for emergencies only.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('symbol')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('spot_price')
                    ->money('eur')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bid_price')->money('eur'),
                Tables\Columns\TextColumn::make('ask_price')->money('eur'),
                Tables\Columns\TextColumn::make('daily_change_pct')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('currency')->options(['EUR' => 'EUR', 'USD' => 'USD']),
            ])
            ->defaultSort('symbol')
            ->poll('5s'); // Auto-refresh the table every 5 seconds for live prices
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit'   => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}

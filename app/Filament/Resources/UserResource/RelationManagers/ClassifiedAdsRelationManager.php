<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class ClassifiedAdsRelationManager extends RelationManager
{
    protected static string $relationship = 'classifiedAds';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('price_value')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, ',', ' ').' NOK' : '—'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Moving\TeamResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class MovingOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'movingOrders';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getLabel(): ?string
    {
        return 'Заказ';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Заказы';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.name')->label('Клиент')->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Запланировано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('estimated_price')
                    ->label('Оценка')
                    ->money('nok'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources\LoyaltyBalanceResource\RelationManagers;

use App\Models\LoyaltyTransaction;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type')
                    ->disabled()
                    ->label('Тип операції'),
                Forms\Components\TextInput::make('points_amount')
                    ->numeric()
                    ->disabled()
                    ->label('Бали'),
                Forms\Components\Textarea::make('description')
                    ->disabled()
                    ->label('Опис'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (LoyaltyTransaction $record) => $record->getTypeLabel())
                    ->color(fn (LoyaltyTransaction $record) => $record->getTypeColor())
                    ->icon(fn (LoyaltyTransaction $record) => $record->getTypeIcon()),

                TextColumn::make('points_amount')
                    ->label('Бали')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').number_format($state, 0, '.', ' ')),

                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action - transactions are created by system
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}

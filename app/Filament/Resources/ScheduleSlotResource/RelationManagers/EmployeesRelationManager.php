<?php

namespace App\Filament\Resources\ScheduleSlotResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $title = 'Исполнители';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                C\TextColumn::make('full_name'),
                Tables\Columns\IconColumn::make('pivot.lead')
                    ->boolean()
                    ->label('Лид'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    // Используем реальное поле БД, чтобы Filament мог сортировать/фильтровать
                    ->recordTitleAttribute('first_name')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Toggle::make('lead')->label('Лид'),
                    ]),
                Tables\Actions\DetachAction::make(),
            ]);
    }
}

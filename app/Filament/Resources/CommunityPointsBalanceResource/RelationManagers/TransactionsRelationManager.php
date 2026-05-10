<?php

namespace App\Filament\Resources\CommunityPointsBalanceResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'История транзакций';

    protected static ?string $modelLabel = 'Транзакция';

    protected static ?string $pluralModelLabel = 'Транзакции';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('delta_points')
                    ->label('Изменение баллов')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('reason_code')
                    ->label('Причина')
                    ->options([
                        'VISIT_COMPLETED' => 'Визит завершён',
                        'BONUS' => 'Бонус',
                        'ADJUSTMENT' => 'Корректировка',
                        'REDEMPTION' => 'Использование',
                    ])
                    ->required(),
                Forms\Components\KeyValue::make('meta')
                    ->label('Метаданные')
                    ->keyLabel('Ключ')
                    ->valueLabel('Значение'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delta_points')
                    ->label('Изменение')
                    ->color(fn ($record) => ($record->delta_points ?? 0) >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($record) => ($record->delta_points ?? 0) >= 0
                        ? '+'.number_format((int) $record->delta_points, 0, ',', ' ')
                        : number_format((int) $record->delta_points, 0, ',', ' ')
                    )
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('reason_code')
                    ->label('Причина')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'VISIT_COMPLETED' => 'Визит завершён',
                        'BONUS' => 'Бонус',
                        'ADJUSTMENT' => 'Корректировка',
                        'REDEMPTION' => 'Использование',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'VISIT_COMPLETED',
                        'info' => 'BONUS',
                        'warning' => 'ADJUSTMENT',
                        'danger' => 'REDEMPTION',
                    ]),
                Tables\Columns\TextColumn::make('meta')
                    ->label('Метаданные')
                    ->formatStateUsing(fn ($record) => $record->meta
                        ? json_encode($record->meta, JSON_UNESCAPED_UNICODE)
                        : '—'
                    )
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->meta
                        ? json_encode($record->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                        : null
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason_code')
                    ->label('Причина')
                    ->options([
                        'VISIT_COMPLETED' => 'Визит завершён',
                        'BONUS' => 'Бонус',
                        'ADJUSTMENT' => 'Корректировка',
                        'REDEMPTION' => 'Использование',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources\SocialCareOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class VisitReportsRelationManager extends RelationManager
{
    // NOTE: Filament v2 RelationManager не поддерживает вложенные отношения вида "careDetails.visitReports".
    // Этот RelationManager временно не используется в SocialCareOrderResource::getRelations(), чтобы не вызывать ошибку.
    // Для доступа к отчётам о визитах используйте отдельные страницы/виджеты Social Care.
    protected static string $relationship = 'visitReports';

    protected static ?string $title = 'Отчёт о визите';

    protected static ?string $modelLabel = 'Отчёт';

    protected static ?string $pluralModelLabel = 'Отчёты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('helper_profile_id')
                    ->label('Помощник')
                    ->relationship('helperProfile', 'display_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Начало визита')
                    ->required(),
                Forms\Components\DateTimePicker::make('ended_at')
                    ->label('Окончание визита')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'COMPLETED' => 'Завершён',
                        'PARTIALLY_COMPLETED' => 'Частично завершён',
                        'NOT_COMPLETED' => 'Не завершён',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('summary')
                    ->label('Краткое описание')
                    ->required()
                    ->rows(3),
                Forms\Components\Select::make('client_mood')
                    ->label('Настроение клиента')
                    ->options([
                        'HAPPY' => 'Радостное',
                        'NEUTRAL' => 'Нейтральное',
                        'CONCERNED' => 'Обеспокоенное',
                    ])
                    ->nullable(),
                Forms\Components\Textarea::make('issues_noted')
                    ->label('Замеченные проблемы')
                    ->rows(3),
                Forms\Components\Toggle::make('followup_recommended')
                    ->label('Рекомендуется повторный визит')
                    ->default(false),
                Forms\Components\Textarea::make('followup_notes')
                    ->label('Заметки для повторного визита')
                    ->rows(3)
                    ->visible(fn ($get) => $get('followup_recommended')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('helperProfile.display_name')
                    ->label('Помощник')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Окончание')
                    ->dateTime()->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'COMPLETED',
                        'warning' => 'PARTIALLY_COMPLETED',
                        'danger' => 'NOT_COMPLETED',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'COMPLETED' => 'Завершён',
                        'PARTIALLY_COMPLETED' => 'Частично завершён',
                        'NOT_COMPLETED' => 'Не завершён',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('client_mood')
                    ->label('Настроение')
                    ->colors([
                        'success' => 'HAPPY',
                        'gray' => 'NEUTRAL',
                        'warning' => 'CONCERNED',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'HAPPY' => 'Радостное',
                        'NEUTRAL' => 'Нейтральное',
                        'CONCERNED' => 'Обеспокоенное',
                        default => '—',
                    }),
                Tables\Columns\IconColumn::make('followup_recommended')
                    ->label('Повторный визит')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'COMPLETED' => 'Завершён',
                        'PARTIALLY_COMPLETED' => 'Частично завершён',
                        'NOT_COMPLETED' => 'Не завершён',
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
            ]);
    }
}

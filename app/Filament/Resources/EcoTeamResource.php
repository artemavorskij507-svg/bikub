<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EcoTeamResource\Pages;
use App\Models\EcoTeam;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class EcoTeamResource extends Resource
{
    protected static ?string $model = EcoTeam::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Eco Disposal';

    protected static ?int $navigationSort = 304;

    protected static bool $shouldRegisterNavigation = true;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Бригада')
                    ->description('Основные параметры ЭКО-бригады, которые используют диспетчеры и аналитика.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название бригады')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Нарвик ЭКО #1')
                            ->helperText('Понятное название для выбора в списках и отчётах.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->helperText('Опционально: состав, особенности, оборудование и т.п.'),
                        Forms\Components\Select::make('vehicle_type')
                            ->label('Тип транспорта')
                            ->options([
                                'van' => 'Фургон',
                                'truck_small' => 'Малый грузовик',
                                'truck_large' => 'Большой грузовик',
                                'trailer' => 'Прицеп',
                            ])
                            ->searchable()
                            ->default('van')
                            ->helperText('Выберите тип транспорта, которым обычно пользуется бригада.'),
                        Forms\Components\TextInput::make('vehicle_capacity_m3')
                            ->label('Объем кузова, м³')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.1')
                            ->helperText('Ориентировочный полезный объём кузова.'),
                        Forms\Components\TextInput::make('vehicle_max_weight_kg')
                            ->label('Грузоподъемность, кг')
                            ->numeric()
                            ->minValue(0)
                            ->step('1')
                            ->helperText('Максимальный допустимый вес загрузки.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->helperText('Отключите, если бригада временно недоступна для назначения.'),
                    ])
                    ->columns(2),
                // TODO: RelationManager пользователей/исполнителей, если в проекте есть готовый паттерн.
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('vehicle_type')
                    ->label('Транспорт')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'van' => 'Фургон',
                            'truck_small' => 'Малый грузовик',
                            'truck_large' => 'Большой грузовик',
                            'trailer' => 'Прицеп',
                            default => $state,
                        };
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle_capacity_m3')
                    ->label('Объем, м³')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1, ',', ' ') : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vehicle_max_weight_kg')
                    ->label('Макс. вес, кг')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', ' ') : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('secondary')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->label('Тип транспорта')
                    ->options([
                        'van' => 'Фургон',
                        'truck_small' => 'Малый грузовик',
                        'truck_large' => 'Большой грузовик',
                        'trailer' => 'Прицеп',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Выключенные')
                    ->queries(
                        true: fn ($query) => $query->where('is_active', true),
                        false: fn ($query) => $query->where('is_active', false),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (EcoTeam $record) => $record->is_active ? 'Выключить' : 'Включить')
                    ->icon(fn (EcoTeam $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (EcoTeam $record) => $record->is_active ? 'warning' : 'success')
                    ->action(function (EcoTeam $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Включить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => true]);
                        }
                    }),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Выключить')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_active' => false]);
                        }
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEcoTeams::route('/'),
            'create' => Pages\CreateEcoTeam::route('/create'),
            'edit' => Pages\EditEcoTeam::route('/{record}/edit'),
        ];
    }
}

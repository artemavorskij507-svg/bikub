<?php

namespace App\Filament\Resources\Moving;

use App\Filament\Resources\Moving\MovingItemResource\Pages;
use App\Models\Moving\MovingItem;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class MovingItemResource extends Resource
{
    protected static ?string $model = MovingItem::class;

    protected static ?string $navigationGroup = 'Moving';

    protected static ?string $navigationLabel = 'Moving Items';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 502;

    protected static ?string $modelLabel = 'предмет переезда';

    protected static ?string $pluralModelLabel = 'предметы переезда';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->schema([
                    TextInput::make('moving_order_id')
                        ->label('ID заказа переезда')
                        ->numeric()
                        ->required(),
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('category')
                        ->label('Категория')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('volume')
                        ->label('Объем, м³')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),
                    TextInput::make('weight')
                        ->label('Вес, кг')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Количество')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    Toggle::make('requires_assembly')
                        ->label('Нужна сборка')
                        ->default(false),
                    Toggle::make('is_fragile')
                        ->label('Хрупкий')
                        ->default(false),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                    Textarea::make('notes')
                        ->label('Заметки')
                        ->rows(3),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('movingOrder.id')
                    ->label('Заказ')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Категория')
                    ->color(fn ($state = null): string => match (mb_strtolower((string) $state)) {
                        'меблі', 'furniture' => 'success',
                        'техніка', 'electronics', 'tech' => 'warning',
                        'коробки', 'boxes' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('volume')
                    ->label('Объем, м³')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('weight')
                    ->label('Вес, кг')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Кол-во')
                    ->sortable(),
                TextColumn::make('total_volume')
                    ->label('Итоговый объем')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' м³')
                    ->toggleable(),
                TextColumn::make('total_weight')
                    ->label('Итоговый вес')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' кг')
                    ->toggleable(),
                IconColumn::make('requires_assembly')
                    ->label('Сборка')
                    ->boolean(),
                IconColumn::make('is_fragile')
                    ->label('Хрупкий')
                    ->boolean()
                    ->color(fn ($state = null): string => ((bool) $state) ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('moving_order_id')
                    ->label('Заказ')
                    ->relationship('movingOrder', 'id')
                    ->searchable(),
                SelectFilter::make('category')
                    ->label('Категория')
                    ->options(fn () => MovingItem::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->toArray()),
                TernaryFilter::make('requires_assembly')
                    ->label('Нужна сборка'),
                TernaryFilter::make('is_fragile')
                    ->label('Хрупкий'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovingItems::route('/'),
            'create' => Pages\CreateMovingItem::route('/create'),
            'edit' => Pages\EditMovingItem::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Moving\MovingOrderResource\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): ?string
    {
        return 'Предмет';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Предмети';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Назва')
                    ->required(),
                TextInput::make('category')
                    ->label('Категорія')
                    ->required(),
                TextInput::make('volume')
                    ->label('Обʼєм, м³')
                    ->numeric()
                    ->required(),
                TextInput::make('weight')
                    ->label('Вага, кг')
                    ->numeric()
                    ->required(),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(1)
                    ->label('Кількість')
                    ->required(),
                Toggle::make('requires_assembly')
                    ->label('Потребує збірки'),
                Toggle::make('is_fragile')
                    ->label('Крихкий'),
                Textarea::make('notes')
                    ->label('Нотатки')
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Назва')->searchable()->sortable(),
                TextColumn::make('category')->label('Категорія')->sortable(),
                TextColumn::make('volume')->label('Обʼєм, м³')->sortable(),
                TextColumn::make('weight')->label('Вага, кг')->sortable(),
                TextColumn::make('quantity')->label('К-ть')->sortable(),
                IconColumn::make('requires_assembly')->label('Збірка')->boolean(),
                IconColumn::make('is_fragile')->label('Крихкий')->boolean(),
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
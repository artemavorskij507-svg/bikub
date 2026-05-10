<?php

namespace App\Filament\Resources\Moving;

use App\Filament\Resources\Moving\MovingOrderTaskResource\Pages;
use App\Models\Moving\MovingOrderTask;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class MovingOrderTaskResource extends Resource
{
    protected static ?string $model = MovingOrderTask::class;

    protected static ?string $navigationGroup = 'Moving';

    protected static ?string $navigationLabel = 'Moving Order Tasks';

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?int $navigationSort = 506;

    protected static ?string $modelLabel = 'moving order task';

    protected static ?string $pluralModelLabel = 'moving order tasks';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('moving_order_id')
                ->label('Moving order')
                ->relationship('movingOrder', 'id')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('task_id')
                ->label('Task')
                ->relationship('task', 'id')
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('task_type')
                ->label('Task type')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('movingOrder.id')
                    ->label('Moving order')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('task.id')
                    ->label('Task')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('task_type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Linked')
                    ->since()
                    ->sortable(),
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
            'index' => Pages\ListMovingOrderTasks::route('/'),
            'create' => Pages\CreateMovingOrderTask::route('/create'),
            'edit' => Pages\EditMovingOrderTask::route('/{record}/edit'),
        ];
    }
}


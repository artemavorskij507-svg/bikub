<?php

namespace App\Filament\Resources\RepairProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class RepairMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Медиа';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Select::make('role')->label('Роль')->options(['before' => 'До',                    'during' => 'В процессе',                    'after' => 'После',                    'general' => 'Общее'])->default('general'),            Forms\Components\Select::make('repair_stage_id')->label('Этап')->relationship('stage', 'name')->searchable(),            Forms\Components\TextInput::make('caption')->label('Подпись')->maxLength(255),            Forms\Components\FileUpload::make('path')->label('Файл')->acceptedFileTypes(['image/*'])->directory('repairs')->disk('public')->required()->visibility('public')->afterStateUpdated(function (callable $set, $state) {
            if ($state) {
                $set('thumbnail_path', $state);
            }
        }),            Forms\Components\Hidden::make('thumbnail_path')])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\ImageColumn::make('thumbnail_path')->label('Превью')->disk('public')->getStateUsing(fn ($record) => $record->thumbnail_path ?? $record->path)->size(60),                Tables\Columns\TextColumn::make('role')->label('Роль')->formatStateUsing(function (?string $state) {
            return match ($state) {
                'before' => 'До',                            'during' => 'В процессе',                            'after' => 'После',                            'general' => 'Общее',                            default => $state,
            };
        }),                Tables\Columns\TextColumn::make('stage.name')->label('Этап')->toggleable(),                Tables\Columns\TextColumn::make('caption')->label('Подпись')->limit(40),                Tables\Columns\TextColumn::make('created_at')->label('Добавлено')->dateTime()->sortable()])->defaultSort('created_at', 'desc')->headerActions([Tables\Actions\CreateAction::make()])->actions([Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

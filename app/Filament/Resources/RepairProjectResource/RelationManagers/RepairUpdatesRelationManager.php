<?php

namespace App\Filament\Resources\RepairProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class RepairUpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';

    protected static ?string $title = 'Обновления';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Select::make('type')->label('Тип обновления')->options(['note' => 'Заметка',                    'status_change' => 'Изменение статуса',                    'milestone' => 'Майлстоун',                    'issue' => 'Проблема',                    'photo_update' => 'Фото-отчёт'])->default('note')->required(),            Forms\Components\Select::make('repair_stage_id')->label('Этап')->relationship('stage', 'name')->searchable(),            Forms\Components\TextInput::make('title')->label('Заголовок')->maxLength(255),            Forms\Components\Textarea::make('body')->label('Описание')->rows(4),            Forms\Components\TextInput::make('progress_percent')->label('Прогресс проекта (%)')->numeric()->minValue(0)->maxValue(100)->placeholder('0–100')->helperText('Оценка прогресса на момент обновления, в процентах.'),            Forms\Components\Select::make('status_snapshot')->label('Статус проекта')->options(['draft' => 'Черновик',                    'assessment' => 'Оценка',                    'estimating' => 'Смета',                    'scheduled' => 'Запланирован',                    'in_progress' => 'В работе',                    'on_hold' => 'Пауза',                    'completed' => 'Завершён',                    'cancelled' => 'Отменён'])])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('created_at')->label('Когда')->dateTime()->sortable(),                Tables\Columns\BadgeColumn::make('type')->label('Тип')->colors(['primary' => 'note',                        'warning' => 'issue',                        'success' => 'milestone',                        'info' => 'status_change',                        'secondary' => 'photo_update'])->formatStateUsing(fn (string $state) => match ($state) {
            'note' => 'Заметка',                        'status_change' => 'Статус',                        'milestone' => 'Майлстоун',                        'issue' => 'Проблема',                        'photo_update' => 'Фото',                        default => $state,
        })->sortable(),                Tables\Columns\TextColumn::make('title')->label('Заголовок')->limit(40)->searchable(),                Tables\Columns\TextColumn::make('progress_percent')->label('Прогресс')->suffix('%')->sortable(),                Tables\Columns\TextColumn::make('status_snapshot')->label('Статус проекта')->sortable(),                Tables\Columns\TextColumn::make('author.name')->label('Автор')->sortable()->toggleable()])->defaultSort('created_at', 'desc')->headerActions([Tables\Actions\CreateAction::make()])->actions([Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

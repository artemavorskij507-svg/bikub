<?php

namespace App\Filament\Resources\RepairProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';

    protected static ?string $title = 'Этапы проекта';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\TextInput::make('name')->label('Название этапа')->required()->maxLength(255),                Forms\Components\Textarea::make('description')->label('Описание')->rows(3)->nullable(),                Forms\Components\TextInput::make('sequence')->label('Порядок')->numeric()->required()->default(10),                Forms\Components\Select::make('status')->label('Статус')->options(['planned' => 'Запланировано',                        'in_progress' => 'В работе',                        'completed' => 'Завершено',                        'cancelled' => 'Отменено'])->required()->default('planned'),                Forms\Components\DateTimePicker::make('planned_start_at')->label('Планируемое начало')->nullable(),                Forms\Components\DateTimePicker::make('planned_finish_at')->label('Планируемое окончание')->nullable(),                Forms\Components\DateTimePicker::make('actual_start_at')->label('Фактическое начало')->nullable(),                Forms\Components\DateTimePicker::make('actual_finish_at')->label('Фактическое окончание')->nullable(),                Forms\Components\TextInput::make('progress_percent')->label('Прогресс (%)')->numeric()->minValue(0)->maxValue(100)->default(0)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('sequence')->label('№')->sortable(),                Tables\Columns\TextColumn::make('name')->label('Название')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['secondary' => 'planned',                        'warning' => 'in_progress',                        'success' => 'completed',                        'danger' => 'cancelled'])->sortable(),                Tables\Columns\TextColumn::make('progress_percent')->label('Прогресс')->suffix('%')->sortable(),                Tables\Columns\TextColumn::make('planned_start_at')->label('Планируемое начало')->dateTime()->sortable()->toggleable(),                Tables\Columns\TextColumn::make('actual_finish_at')->label('Фактическое окончание')->dateTime()->sortable()->toggleable()])->defaultSort('sequence', 'asc')->filters([Tables\Filters\SelectFilter::make('status')->label('Статус')->options(['planned' => 'Запланировано',                        'in_progress' => 'В работе',                        'completed' => 'Завершено',                        'cancelled' => 'Отменено'])])->headerActions([Tables\Actions\CreateAction::make()])->actions([Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairStageResource\Pages;
use App\Models\RepairStage;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RepairStageResource extends Resource
{
    protected static ?string $model = RepairStage::class;

    protected static ?string $navigationIcon = 'heroicon-o-menu';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 405;

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Базовая информация')->description('К какому проекту относится этап и как он называется.')->schema([Forms\Components\Select::make('repair_project_id')->label('Проект')->relationship('project', 'title')->searchable()->preload()->required()->helperText('Выберите проект ремонта, в рамках которого создаётся этот этап.'),                        Forms\Components\TextInput::make('name')->label('Этап')->required()->placeholder('Например, “Демонтаж”, “Черновые работы”, “Финишная отделка”')->helperText('Краткое и понятное название этапа, которое будет видно в дашбордах.'),                        Forms\Components\Textarea::make('description')->label('Описание')->rows(3)->placeholder('Что конкретно делаем на этом этапе, материалы, риски, особые условия.'),                        Forms\Components\TextInput::make('sequence')->label('Порядок')->numeric()->minValue(1)->default(1)->required()->helperText('Порядковый номер этапа в рамках проекта: 1, 2, 3...'),                        Forms\Components\Select::make('status')->label('Статус')->options(['planned' => 'Запланирован',                                'in_progress' => 'В работе',                                'completed' => 'Завершён',                                'cancelled' => 'Отменён'])->required()->default('planned')->helperText('Текущее состояние этапа. Для новых этапов обычно “Запланирован”.')])->columns(2),                Forms\Components\Section::make('Сроки выполнения')->description('Плановые и фактические даты для контроля сроков.')->schema([Forms\Components\DateTimePicker::make('planned_start_at')->label('Планируемый старт')->nullable()->helperText('Ожидаемая дата начала работ по этапу.'),                        Forms\Components\DateTimePicker::make('planned_finish_at')->label('Планируемое завершение')->nullable()->helperText('Ожидаемая дата завершения этапа.'),                        Forms\Components\DateTimePicker::make('actual_start_at')->label('Фактический старт')->nullable()->helperText('Заполняется, когда бригада реально приступила к этапу.'),                        Forms\Components\DateTimePicker::make('actual_finish_at')->label('Фактическое завершение')->nullable()->helperText('Фактическая дата завершения этапа.')])->columns(2),                Forms\Components\Section::make('Контроль прогресса')->schema([Forms\Components\TextInput::make('progress_percent')->label('Прогресс (%)')->numeric()->minValue(0)->maxValue(100)->default(0)->helperText('Примерная завершённость этапа: 0 — не начат, 100 — завершён.')])->columns(1)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('project.title')->label('Проект')->searchable()->sortable(),                Tables\Columns\TextColumn::make('name')->label('Этап')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['warning' => 'planned',                        'info' => 'in_progress',                        'success' => 'completed',                        'danger' => 'cancelled'])->sortable(),                Tables\Columns\TextColumn::make('sequence')->label('Очередь')->sortable(),                Tables\Columns\TextColumn::make('progress_percent')->label('Прогресс (%)')->sortable()])->filters([])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRepairStages::route('/'),            'create' => Pages\CreateRepairStage::route('/create'),            'edit' => Pages\EditRepairStage::route('/{record}/edit')];
    }
}

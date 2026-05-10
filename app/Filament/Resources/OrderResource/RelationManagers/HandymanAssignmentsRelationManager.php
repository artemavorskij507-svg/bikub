<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\HandymanAssignment;
use App\Services\Handyman\HandymanAssignmentService;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class HandymanAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'handymanAssignments';

    protected static ?string $title = 'Назначения мастеров';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Select::make('executor_profile_id')->label('Мастер')->relationship('executorProfile', 'id')->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? "Профиль #{$record->id}")->searchable()->required(),                Forms\Components\Select::make('status')->label('Статус')->options(['proposed' => 'Предложено',                        'accepted' => 'Принято',                        'declined' => 'Отклонено',                        'reassigned' => 'Переназначено',                        'cancelled' => 'Отменено',                        'completed' => 'Завершено'])->required()->default('proposed'),                Forms\Components\DateTimePicker::make('planned_start_at')->label('Планируемое начало')->nullable(),                Forms\Components\DateTimePicker::make('planned_finish_at')->label('Планируемое окончание')->nullable(),                Forms\Components\TextInput::make('score')->label('Score')->numeric()->nullable(),                Forms\Components\Toggle::make('is_primary')->label('Основное назначение')->default(false)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('executorProfile.user.name')->label('Мастер')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['warning' => 'proposed',                        'success' => 'accepted',                        'danger' => 'declined',                        'secondary' => 'reassigned',                        'gray' => 'cancelled',                        'primary' => 'completed'])->sortable(),                Tables\Columns\TextColumn::make('planned_start_at')->label('Планируемое начало')->dateTime()->sortable()->toggleable(),                Tables\Columns\TextColumn::make('planned_finish_at')->label('Планируемое окончание')->dateTime()->sortable()->toggleable(),                Tables\Columns\TextColumn::make('score')->label('Score')->sortable()->toggleable(),                Tables\Columns\IconColumn::make('is_primary')->label('Основное')->boolean()])->filters([Tables\Filters\SelectFilter::make('status')->label('Статус')->options(['proposed' => 'Предложено',                        'accepted' => 'Принято',                        'declined' => 'Отклонено',                        'reassigned' => 'Переназначено',                        'cancelled' => 'Отменено',                        'completed' => 'Завершено'])])->headerActions([Tables\Actions\Action::make('generate_candidates')->label('Сгенерировать кандидатов')->icon('heroicon-o-sparkles')->color('primary')->requiresConfirmation()->action(function ($livewire) {
            $order = $livewire->getOwnerRecord();
            /** @var HandymanAssignmentService $service */ $service = app(HandymanAssignmentService::class);
            $assignments = $service->proposeAssignmentsForOrder($order);
            $livewire->notify('success', "Создано назначений: {$assignments->count()}");
        })->visible(fn ($livewire) => $livewire->getOwnerRecord()->handymanDetails !== null),                Tables\Actions\CreateAction::make()])->actions([Tables\Actions\Action::make('accept')->label('Принять')->icon('heroicon-o-check')->color('success')->requiresConfirmation()->action(function (HandymanAssignment $record, $livewire) {                        /** @var HandymanAssignmentService $service */ $service = app(HandymanAssignmentService::class);
            $service->acceptAssignment($record);
            $livewire->notify('success', 'Назначение принято');
        })->visible(fn (HandymanAssignment $record) => $record->status === 'proposed'),                Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

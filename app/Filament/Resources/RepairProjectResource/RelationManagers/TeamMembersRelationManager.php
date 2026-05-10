<?php

namespace App\Filament\Resources\RepairProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class TeamMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'teamMembers';

    protected static ?string $title = 'Команда проекта';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Select::make('executor_profile_id')->label('Мастер')->relationship('executorProfile', 'id')->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Профиль #{$record->id}")->searchable()->preload()->required()->helperText('Выберите исполнителя из списка доступных профилей мастеров.'),                Forms\Components\Select::make('role')->label('Роль')->options(['FOREMAN' => 'Прораб',                        'ELECTRICIAN' => 'Электрик',                        'PLUMBER' => 'Сантехник',                        'CARPENTER' => 'Плотник',                        'PAINTER' => 'Маляр',                        'TILER' => 'Плиточник'])->required(),                Forms\Components\Toggle::make('is_lead')->label('Ведущий специалист')->default(false),                Forms\Components\Textarea::make('notes')->label('Заметки')->rows(3)->nullable()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('executorProfile.user.name')->label('Мастер')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('role')->label('Роль')->sortable(),                Tables\Columns\IconColumn::make('is_lead')->label('Ведущий')->boolean(),                Tables\Columns\TextColumn::make('notes')->label('Заметки')->limit(50)->toggleable()])->filters([Tables\Filters\SelectFilter::make('role')->label('Роль')->options(['FOREMAN' => 'Прораб',                        'ELECTRICIAN' => 'Электрик',                        'PLUMBER' => 'Сантехник',                        'CARPENTER' => 'Плотник',                        'PAINTER' => 'Маляр',                        'TILER' => 'Плиточник'])])->headerActions([Tables\Actions\CreateAction::make()])->actions([Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

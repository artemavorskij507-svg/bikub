<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairTeamMemberResource\Pages;
use App\Models\RepairTeamMember;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RepairTeamMemberResource extends Resource
{
    protected static ?string $model = RepairTeamMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 406;

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Проект и мастер')->description('Выберите ремонтный проект и профиль исполнителя, которого хотите добавить в команду.')->schema([Forms\Components\Select::make('repair_project_id')->label('Проект')->relationship('project', 'title')->searchable()->preload()->required()->helperText('Проект, в рамках которого будет работать этот специалист.'),                        Forms\Components\Select::make('executor_profile_id')->label('Исполнитель')->relationship('executorProfile', 'id')->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Профиль #{$record->id}")->searchable()->preload()->required()->helperText('Выберите мастера из списка доступных профилей исполнителей.')])->columns(2),                Forms\Components\Section::make('Роль и ответственность')->description('Определите роль мастера и его статус в команде проекта.')->schema([Forms\Components\Select::make('role')->label('Роль')->options(['FOREMAN' => 'Прораб',                                'ELECTRICIAN' => 'Электрик',                                'PLUMBER' => 'Сантехник',                                'CARPENTER' => 'Плотник',                                'PAINTER' => 'Маляр',                                'TILER' => 'Плиточник'])->required()->helperText('Роль мастера в рамках проекта (например, прораб или электрик).'),                        Forms\Components\Toggle::make('is_lead')->label('Ведущий специалист')->default(false)->helperText('Отметьте, если этот мастер является ведущим по своему направлению.')])->columns(2),                Forms\Components\Section::make('Заметки')->description('Добавьте любые дополнительные комментарии по этому участнику команды.')->schema([Forms\Components\Textarea::make('notes')->label('Заметки')->rows(3)->nullable()->placeholder('Например: отвечает за электрику в санузлах, работает по будням до 18:00.')])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('project.title')->label('Проект')->searchable()->sortable(),                Tables\Columns\TextColumn::make('executorProfile.user.name')->label('Исполнитель')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('role')->label('Роль')->sortable(),                Tables\Columns\IconColumn::make('is_lead')->label('Лидер')->boolean(),                Tables\Columns\TextColumn::make('updated_at')->label('Обновлено')->dateTime()->since()->sortable()])->filters([])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRepairTeamMembers::route('/'),            'create' => Pages\CreateRepairTeamMember::route('/create'),            'edit' => Pages\EditRepairTeamMember::route('/{record}/edit')];
    }
}

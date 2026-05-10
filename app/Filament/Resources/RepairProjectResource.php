<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairProjectResource\Pages;
use App\Filament\Resources\RepairProjectResource\RelationManagers;
use App\Models\RepairProject;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class RepairProjectResource extends Resource
{
    protected static ?string $model = RepairProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 404;

    public static function form(Form $form): Form
    {
        return $form->schema([Section::make('Основное')->description('Связь с заказом, клиентом и базовые параметры проекта.')->schema([Forms\Components\Select::make('order_id')->label('Заказ')->relationship('order', 'order_number')->searchable()->preload()->required()->helperText('Выберите базовый заказ, из которого был создан проект ремонта.'),                        Forms\Components\Select::make('client_profile_id')->label('Профиль клиента')->relationship('clientProfile', 'full_name')->searchable()->preload()->nullable()->helperText('Опционально: используйте, если у клиента есть отдельный профиль с контактами.'),                        Forms\Components\Select::make('project_manager_id')->label('Прораб')->relationship('projectManager', 'id')->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? "Профиль #{$record->id}")->searchable()->preload()->nullable()->helperText('Ответственный мастер / прораб за весь проект. Можно назначить позже.'),                        Forms\Components\TextInput::make('title')->label('Название проекта')->required()->placeholder('Например, “Ремонт ванной на Storgata 12”')->helperText('Краткое, понятное название — будет видно во всех списках.'),                        Forms\Components\Textarea::make('description')->label('Описание')->rows(4)->placeholder('Кратко опишите объём работ, особые пожелания клиента, ограничения по времени и т.п.')->helperText('Необязательное, но полезное поле для диспетчеров и мастеров.'),                        Forms\Components\Select::make('status')->label('Статус')->options(['draft' => 'Черновик',                                'assessment' => 'Оценка',                                'estimating' => 'Смета',                                'scheduled' => 'Запланирован',                                'in_progress' => 'В работе',                                'on_hold' => 'Пауза',                                'completed' => 'Завершён',                                'cancelled' => 'Отменён'])->required()->default('assessment')->helperText('Текущее состояние проекта. Для новых проектов чаще всего “Оценка” или “Смета”.')])->columns(2),                Section::make('Адрес объекта')->description('Где выполняются работы.')->schema([Forms\Components\TextInput::make('address_line')->label('Адрес')->placeholder('Улица, дом, квартира')->helperText('Фактический адрес объекта ремонта.'),                        Forms\Components\TextInput::make('postal_code')->label('Индекс')->maxLength(16)->placeholder('Например, 8514'),                        Forms\Components\TextInput::make('city')->label('Город')->placeholder('Например, Narvik')])->columns(3),                Section::make('Планирование и фактические сроки')->description('Когда планируется и когда фактически выполняется проект.')->schema([Forms\Components\DateTimePicker::make('planned_start_at')->label('Планируемый старт')->helperText('Ожидаемая дата начала работ.')->nullable(),                        Forms\Components\DateTimePicker::make('planned_finish_at')->label('Планируемое завершение')->helperText('Ожидаемая дата окончания проекта.')->nullable(),                        Forms\Components\DateTimePicker::make('actual_start_at')->label('Фактический старт')->helperText('Заполняется, когда бригада реально вышла на объект.')->nullable(),                        Forms\Components\DateTimePicker::make('actual_finish_at')->label('Фактическое завершение')->helperText('Заполняется после полного завершения работ.')->nullable()])->columns(2),                Section::make('Бюджет и прогресс')->description('Оценка стоимости и общий прогресс по проекту.')->schema([Forms\Components\TextInput::make('budget_estimate_minor')->label('Бюджет план (в коп.)')->numeric()->minValue(0)->nullable()->helperText('Плановый бюджет в копейках. Например, 150000 = 1 500 NOK.'),                        Forms\Components\TextInput::make('budget_actual_minor')->label('Бюджет факт (в коп.)')->numeric()->minValue(0)->nullable()->helperText('Фактические затраты по проекту в копейках (можно заполнять постепенно).'),                        Forms\Components\TextInput::make('design_project_url')->label('Ссылка на дизайн-проект')->url()->nullable()->placeholder('https://...'),                        Forms\Components\Textarea::make('notes')->label('Заметки')->rows(3)->placeholder('Любые внутренние комментарии, договорённости с клиентом, риски и т.п.'),                        Forms\Components\TextInput::make('overall_progress_percent')->label('Общий прогресс (%)')->numeric()->minValue(0)->maxValue(100)->nullable()->helperText('Оценка завершённости проекта: 0 — не начат, 100 — полностью завершён.')])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('title')->label('Проект')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['primary',                        'warning' => ['assessment', 'estimating', 'scheduled'],                        'success' => ['completed'],                        'danger' => ['cancelled'],                        'info' => ['in_progress']])->sortable(),                Tables\Columns\TextColumn::make('order.order_number')->label('Заказ')->sortable()->searchable(),                Tables\Columns\TextColumn::make('projectManager.user.name')->label('Прораб')->sortable()->toggleable(),                Tables\Columns\TextColumn::make('planned_start_at')->label('План. старт')->dateTime()->sortable(),                Tables\Columns\TextColumn::make('actual_finish_at')->label('Факт. завершение')->dateTime()->sortable(),                Tables\Columns\TextColumn::make('overall_progress_percent')->label('Прогресс')->suffix('%')->sortable()])->filters([Tables\Filters\SelectFilter::make('status')->label('Статус')->options(['draft' => 'Черновик',                        'assessment' => 'Оценка',                        'estimating' => 'Смета',                        'scheduled' => 'Запланирован',                        'in_progress' => 'В работе',                        'on_hold' => 'Пауза',                        'completed' => 'Завершён',                        'cancelled' => 'Отменён']),                Tables\Filters\SelectFilter::make('project_manager_id')->label('Прораб')->relationship('projectManager', 'id')->options(fn () => \App\Models\Moving\ExecutorProfile::query()->orderBy('id')->pluck('id', 'id')),                Tables\Filters\Filter::make('city')->form([Forms\Components\TextInput::make('city')->label('Город')->placeholder('Введите город')])->query(function (Builder $query, array $data): Builder {
            return $query->when($data['city'], fn (Builder $query, $city): Builder => $query->where('city', 'ilike', "%{$city}%"));
        })])->actions([Tables\Actions\Action::make('dashboard')->label('Dashboard')->icon('heroicon-o-chart-bar')->url(fn (RepairProject $record): string => static::getUrl('dashboard', ['record' => $record])),                Tables\Actions\ViewAction::make(),                Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\StagesRelationManager::class,            RelationManagers\TeamMembersRelationManager::class,            RelationManagers\WorkWarrantiesRelationManager::class,            RelationManagers\ClaimsRelationManager::class,            RelationManagers\RepairUpdatesRelationManager::class,            RelationManagers\RepairMediaRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRepairProjects::route('/'),            'create' => Pages\CreateRepairProject::route('/create'),            'edit' => Pages\EditRepairProject::route('/{record}/edit'),            'dashboard' => Pages\ProjectDashboard::route('/{record}/dashboard')];
    }
}

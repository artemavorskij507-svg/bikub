<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceTypeResource\Pages;
use App\Models\Organization;
use App\Models\ServiceType;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Str;

class ServiceTypeResource extends Resource
{
    protected static ?string $model = ServiceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $pluralLabel = 'Услуги';

    protected static ?string $slug = 'service-types';

    // TODO fixed by Cursor: normalize navigation group name to unified 'Справочники и контент'
    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('org_id')
                    ->default(fn () => self::resolveOrgId())
                    ->dehydrateStateUsing(fn () => self::resolveOrgId())
                    ->required(),
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код услуги')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Уникальный код услуги (например: care.l1.med_delivery)'),
                        Forms\Components\Select::make('service_category_id')
                            ->label('Категория')
                            ->relationship('serviceCategory', 'name')
                            ->required()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\KeyValue::make('default_pricing')
                            ->label('Ценообразование')
                            ->keyLabel('Параметр')
                            ->valueLabel('Значение')
                            ->helperText('Базовая цена, длительность, надбавки'),
                        Forms\Components\TagsInput::make('skills')
                            ->label('Навыки/требования')
                            ->helperText('Необходимые навыки исполнителя'),
                        Forms\Components\TagsInput::make('inventory')
                            ->label('Инвентарь')
                            ->helperText('Необходимый инвентарь'),
                    ])
                    ->columns(1),

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('category')
                            ->label('Старая категория (deprecated)')
                            ->default('care'),
                        Forms\Components\TextInput::make('icon')
                            ->label('Иконка'),
                        Forms\Components\Textarea::make('features')
                            ->label('Особенности'),
                        Forms\Components\TextInput::make('estimated_duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('code')
                    ->label('Код')
                    ->colors(['primary'])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('serviceCategory.name')
                    ->label('Категория')
                    ->colors([
                        'success' => ['Alle Care', 'Care'],
                        'warning' => ['Alle Eco', 'Eco', 'Market'],
                        'danger' => ['Alle Tow', 'Tow'],
                        'info' => ['Alle Rent', 'Rent', 'Shuttle'],
                        'secondary' => ['Alle Shuttle', 'Master', 'Food'],
                        'primary',
                    ])
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_pricing.base')
                    ->label('Базовая цена')
                    ->formatStateUsing(fn ($state) => $state ? $state.' NOK' : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('skills')
                    ->label('Навыки')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' навыков' : '-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_category_id')
                    ->label('Категория')
                    ->relationship('serviceCategory', 'name')
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->action(fn ($records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Деактивировать')
                    ->action(fn ($records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceTypes::route('/'),
            'create' => Pages\CreateServiceType::route('/create'),
            'edit' => Pages\EditServiceType::route('/{record}/edit'),
        ];
    }

    protected static function resolveOrgId(): ?string
    {
        $userOrg = auth()->user()?->default_org_id;
        if ($userOrg && Str::isUuid($userOrg)) {
            return $userOrg;
        }

        return Organization::query()->value('id');
    }
}

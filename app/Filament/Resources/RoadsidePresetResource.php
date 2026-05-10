<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoadsidePresetResource\Pages;
use App\Models\RoadsidePreset;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class RoadsidePresetResource extends Resource
{
    protected static ?string $model = RoadsidePreset::class;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Виды работ Roadside';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 603;

    // fix: standardize navigation gating for v2 (temporary permissive for admin/operator/dispatcher)
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'operator', 'dispatcher']);
        }

        return true;
    }

    // fix: relax access to avoid 403 during setup (any authenticated user)
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->description('Название и код пресета, которые видят диспетчеры и клиенты.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код (slug)')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('jump_start, flat_tire, fuel_delivery')
                            ->helperText('Технический код без пробелов, латиница и нижнее подчёркивание. Используется в интеграциях и логике.')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('label')
                            ->label('Название для клиентов')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Прикурить авто, Замена колеса, Привезти топливо')
                            ->helperText('Понятное название, которое увидит клиент и оператор.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->placeholder('Кратко опишите, что делает бригада: какие работы входят, ограничения по расстоянию и т.п.')
                            ->helperText('Необязательно, но помогает оператору правильно подобрать услугу.'),
                    ])
                    ->columns(2),
                Section::make('Тип услуги и ценообразование')
                    ->description('Выберите тип roadside‑услуги и задайте базовую стоимость.')
                    ->schema([
                        Forms\Components\Select::make('service_type')
                            ->label('Тип услуги')
                            ->options([
                                'roadside_assistance' => 'Помощь на дороге',                                'vehicle_inspection' => 'Осмотр перед покупкой',
                                'vehicle_transport' => 'Эвакуация',
                            ])
                            ->required()
                            ->helperText('От типа зависит маршрут обработки заказа и отображение в дашбордах.'),
                        Forms\Components\TextInput::make('base_price')
                            ->label('Базовая цена')
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->prefix('NOK')
                            ->nullable()
                            ->placeholder('Например: 990')
                            ->helperText('Необязательно. Если оставить пустым, цена будет рассчитана по общим правилам тарификации.'),
                        Forms\Components\Toggle::make('requires_partner')
                            ->label('Требует партнёра-эвакуатора')
                            ->helperText('Включайте для эвакуации и сложных случаев, которые всегда выполняет внешний партнёр.')
                            ->default(false),
                    ])
                    ->columns(2),
                Section::make('Порядок и активность')
                    ->description('Управление сортировкой в списке и доступностью пресета.')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Чем меньше число, тем выше пресет в списке выбора.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->helperText('Неактивные пресеты скрываются из выбора, но остаются в истории заказов.'),
                    ])->columns(2),
                Section::make('Технические метаданные')
                    ->description('Дополнительные настройки для интеграций и автоматизации (опционально).')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->helperText('Например: vendor_code, internal_priority, product_id. Необязательное поле.')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Код')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Название')
                    ->searchable()->sortable(),
                TextColumn::make('service_type')
                    ->label('Тип услуги')
                    ->formatStateUsing(fn ($state = null): string => match ((string) $state) {
                        'roadside_assistance' => 'Помощь на дороге',
                        'vehicle_inspection' => 'Осмотр перед покупкой',
                        'vehicle_transport' => 'Эвакуация',
                        default => (string) $state,
                    })
                    ->color(fn ($state = null): string => match ((string) $state) {
                        'roadside_assistance' => 'primary',
                        'vehicle_inspection' => 'success',
                        'vehicle_transport' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('base_price')
                    ->label('Базовая цена')
                    // fix: safe numeric rendering to avoid Money exception
                    ->formatStateUsing(fn ($state) => ($state === null || $state === '') ? '—' : (number_format((float) $state, 2, '.', ' ').' NOK')),
                IconColumn::make('requires_partner')
                    ->label('Требует партнёра')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->label('Тип услуги')
                    ->options([
                        'roadside_assistance' => 'Помощь на дороге',
                        'vehicle_inspection' => 'Осмотр перед покупкой',
                        'vehicle_transport' => 'Эвакуация',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoadsidePresets::route('/'),
            'create' => Pages\CreateRoadsidePreset::route('/create'),
            'edit' => Pages\EditRoadsidePreset::route('/{record}/edit'),
        ];
    }
}

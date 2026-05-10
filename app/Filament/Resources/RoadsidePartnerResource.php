<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoadsidePartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class RoadsidePartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Партнёры-эвакуаторы';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 606;

    protected static ?string $slug = 'roadside-partners';

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

    // fix: relax access to avoid hiding from navigation (visible to any authenticated user)
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->roadside();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основное')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Слаг')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'towing_service' => 'Эвакуатор / буксировка',
                                'roadside_mobile' => 'Мобильная помощь',
                                'repair_shop' => 'СТО / сервис',
                                'inspection_center' => 'Осмотр / диагностика',
                            ])
                            ->required()
                            ->default('towing_service'),
                        Forms\Components\Select::make('geo_zone_id')
                            ->label('Геозона')
                            ->relationship('geoZone', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('priority')
                            ->label('Приоритет')
                            ->numeric()
                            ->default(100)
                            ->helperText('Чем меньше — тем приоритетнее для автоназначения')
                            ->minValue(1)
                            ->maxValue(999),
                        Forms\Components\Toggle::make('active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Доступен для заказов')
                            ->default(true),
                        Forms\Components\TextInput::make('support_phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('support_email')->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('emergency_distance_km')
                            ->label('Макс. расстояние (км)')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('emergency_price_base')
                            ->label('Базовая цена')
                            ->numeric()
                            ->prefix('NOK')
                            ->minValue(0),
                        Forms\Components\TextInput::make('emergency_price_per_km')
                            ->label('Цена за км')
                            ->numeric()
                            ->prefix('NOK')
                            ->minValue(0),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Roadside-способности')
                    ->schema([
                        Forms\Components\CheckboxList::make('capabilities')
                            ->label('Возможности')
                            ->options([
                                'jump_start' => 'Прикуривание',                                'wheel_change' => 'Замена колеса',
                                'fuel_delivery' => 'Подвоз топлива',
                                'towing' => 'Эвакуация',
                                'winching' => 'Вытаскивание',
                                'diagnostics' => 'Диагностика / осмотр',
                            ])
                            ->columns(2),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Партнёр')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state = null): string => match ((string) $state) {
                        'towing_service' => 'Эвакуатор',
                        'roadside_mobile' => 'Мобильная помощь',
                        'repair_shop' => 'СТО',
                        'inspection_center' => 'Осмотр',
                        default => (string) $state,
                    })
                    ->color(fn ($state = null): string => match ((string) $state) {
                        'towing_service' => 'danger',
                        'roadside_mobile' => 'warning',
                        'repair_shop' => 'info',
                        'inspection_center' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('geoZone.name')
                    ->label('Геозона')
                    ->searchable()
                    ->sortable()
                    ->default('—'),
                TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable()
                    ->default(100),
                TextColumn::make('capabilities')
                    ->label('Возможности')
                    ->formatStateUsing(function ($state) {
                        if (! $state || ! is_array($state)) {
                            return '—';
                        }
                        $labels = [
                            'jump_start' => 'Прикурить',
                            'wheel_change' => 'Колесо',
                            'fuel_delivery' => 'Топливо',
                            'towing' => 'Эвакуация',
                            'winching' => 'Вытаскивание',
                            'diagnostics' => 'Диагностика',
                        ];

                        return implode(', ', array_map(fn ($cap) => $labels[$cap] ?? $cap, $state));
                    })
                    ->limit(50),
                IconColumn::make('active')
                    ->label('Активен')
                    ->boolean(),
                IconColumn::make('is_available')
                    ->label('Доступен')
                    ->boolean(),
                TextColumn::make('support_phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('emergency_price_base')
                    ->label('Базовая цена')
                    // fix: safe numeric rendering without Money/numeric() helpers
                    ->formatStateUsing(fn ($state) => ($state === null || $state === '') ? '—' : (number_format((float) $state, 2, '.', ' ').' NOK')),
                TextColumn::make('emergency_price_per_km')
                    ->label('Цена/км')
                    // fix: safe numeric rendering without Money/numeric() helpers
                    ->formatStateUsing(fn ($state) => ($state === null || $state === '') ? '—' : (number_format((float) $state, 2, '.', ' ').' NOK')),
                TextColumn::make('zones_count')
                    ->counts('zones')
                    ->label('Зоны')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'towing_service' => 'Эвакуатор',
                        'roadside_mobile' => 'Мобильная помощь',
                        'repair_shop' => 'СТО / сервис',
                        'inspection_center' => 'Осмотр / диагностика',
                    ]),
                TernaryFilter::make('active')
                    ->label('Активные')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
                TernaryFilter::make('is_available')
                    ->label('Доступные')
                    ->placeholder('Все')
                    ->trueLabel('Только доступные')
                    ->falseLabel('Только недоступные'),
                SelectFilter::make('geo_zone_id')
                    ->label('Геозона')
                    ->relationship('geoZone', 'name')->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')
                    ->label('Вкл/Выкл')
                    ->icon('heroicon-o-lightning-bolt')
                    ->color('secondary')
                    ->action(fn (Partner $record) => $record->update(['active' => ! $record->active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->action(fn ($records) => $records->each->update(['active' => true])),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Деактивировать')
                    ->color('danger')
                    ->action(fn ($records) => $records->each->update(['active' => false])),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoadsidePartners::route('/'),
            'create' => Pages\CreateRoadsidePartner::route('/create'),
            'edit' => Pages\EditRoadsidePartner::route('/{record}/edit'),
        ];
    }
}

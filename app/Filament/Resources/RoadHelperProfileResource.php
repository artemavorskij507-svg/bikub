<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoadHelperProfileResource\Pages;
use App\Models\RoadHelperProfile;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RoadHelperProfileResource extends Resource
{
    protected static ?string $model = RoadHelperProfile::class;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static bool $shouldRegisterNavigation = true;

    // fix: unify Roadside module icon for consistent navigation
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Дорожные помощники';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 604;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Привязка дорожного помощника к пользователю и базовый статус.')
                    ->schema([
                        // привязка к пользователю
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Выберите пользователя, который будет работать как дорожный помощник.'),
                        Forms\Components\Select::make('current_status')
                            ->label('Текущий статус')
                            ->options([
                                'idle' => 'Доступен',
                                'busy' => 'Занят',
                                'on_route' => 'В пути',
                                'offline' => 'Оффлайн',
                            ])
                            ->default('offline')
                            ->helperText('Используется на доске диспетчера для показа доступности помощника.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Транспорт и оборудование')
                    ->description('Информация об автомобиле и доступном оборудовании.')
                    ->schema([
                        Forms\Components\TextInput::make('vehicle_type')
                            ->label('Тип транспорта')
                            ->maxLength(100)
                            ->placeholder('Легковой, Фургон, Пикап')
                            ->helperText('Кратко опишите тип авто, на котором выезжает помощник.'),
                        Forms\Components\TextInput::make('vehicle_model')
                            ->label('Модель авто')
                            ->maxLength(100)
                            ->placeholder('Например: VW Transporter, Toyota Hilux'),
                        Forms\Components\TextInput::make('vehicle_number')
                            ->label('Номер авто')
                            ->maxLength(50)
                            ->placeholder('Регистрационный номер автомобиля'),
                        Forms\Components\KeyValue::make('equipment')
                            ->label('Оборудование')
                            ->keyLabel('Код / тип')
                            ->valueLabel('Описание')
                            ->helperText('Например: winch => Лебёдка, booster => Пусковое устройство.')
                            ->nullable(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Навыки и геопозиция')
                    ->description('Чем именно занимается помощник и где он обычно работает.')
                    ->schema([
                        Forms\Components\KeyValue::make('skills')
                            ->label('Навыки')
                            ->keyLabel('Код навыка')
                            ->valueLabel('Описание')
                            ->helperText('Например: jump_start => Прикурить, tire_change => Замена колеса.')
                            ->nullable(),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('location_lat')
                                ->label('Широта (lat)')
                                ->numeric()
                                ->nullable()
                                ->helperText('Опционально. Используется для расчёта ближайшего помощника.'),
                            Forms\Components\TextInput::make('location_lng')
                                ->label('Долгота (lng)')
                                ->numeric()
                                ->nullable()
                                ->helperText('Опционально. Можно оставить пустым, если нет координат.'),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->user?->email),
                Tables\Columns\BadgeColumn::make('current_status')
                    ->label('Статус')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'idle' => 'Доступен',
                        'busy' => 'Занят',
                        'on_route' => 'В пути',
                        'offline' => 'Оффлайн',
                        default => $state ?? '—',
                    })
                    ->colors([
                        'success' => fn ($state) => $state === 'idle',
                        'warning' => fn ($state) => $state === 'busy',
                        'info' => fn ($state) => $state === 'on_route',
                        'secondary' => fn ($state) => $state === 'offline' || $state === null,
                    ]),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Транспорт')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->label('Номер авто')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('activeOrders_count')
                    ->label('Активных вызовов')
                    ->counts('activeOrders')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('current_status')
                    ->label('Доступность')
                    ->placeholder('Все')
                    ->trueLabel('Доступен / в пути')
                    ->falseLabel('Оффлайн / занят')
                    ->queries(
                        true: fn ($query) => $query->whereIn('current_status', ['idle', 'on_route']),
                        false: fn ($query) => $query->whereNotIn('current_status', ['idle', 'on_route'])->orWhereNull('current_status'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListRoadHelperProfiles::route('/'),
            'create' => Pages\CreateRoadHelperProfile::route('/create'),
            'edit' => Pages\EditRoadHelperProfile::route('/{record}/edit'),
        ];
    }
}

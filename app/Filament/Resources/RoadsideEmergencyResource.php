<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoadsideEmergencyResource\Pages;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RoadsideEmergency;
use App\Models\ServiceType;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;

class RoadsideEmergencyResource extends Resource
{
    protected static ?string $model = RoadsideEmergency::class;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static bool $shouldRegisterNavigation = true;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Экстренные вызовы Roadside';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 608;

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
                Forms\Components\Section::make('Клиент и тип инцидента')
                    ->description('Кто звонит и с какой проблемой на дороге.')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Клиент')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Выберите клиента, от имени которого зарегистрирован экстренный вызов.'),
                        Select::make('incident_type')
                            ->label('Тип инцидента')
                            ->options([
                                'jump_start' => 'Прикуривание',
                                'fuel' => 'Закончилось топливо',
                                'flat_tire' => 'Прокол колеса',
                                'locked_keys' => 'Закрыты ключи',
                                'engine_no_start' => 'Не заводится',
                                'tow_needed' => 'Нужен эвакуатор',
                                'accident' => 'ДТП',
                            ])
                            ->required()
                            ->helperText('Выберите ближайшее по смыслу описание ситуации на дороге.'),
                        Textarea::make('incident_description')
                            ->label('Описание')
                            ->rows(4)
                            ->placeholder('Кратко опишите, что произошло, есть ли препятствия, особенности доступа к авто и т.п.')
                            ->helperText('Эта информация помогает помощнику подготовиться к выезду.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Локация клиента')
                    ->description('Точные координаты для навигации и расчёта ближайшего помощника.')
                    ->schema([
                        TextInput::make('lat')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.0000001)
                            ->minValue(-90)
                            ->maxValue(90)
                            ->placeholder('68.4372')
                            ->helperText('Значение от -90 до 90 (например, для Нарвика: 68.4372). Можно скопировать с карты.')
                            ->required()
                            ->rule('between:-90,90')
                            ->dehydrateStateUsing(fn ($state) => $state !== '' && $state !== null ? (float) $state : null),
                        TextInput::make('lng')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.0000001)
                            ->minValue(-180)
                            ->maxValue(180)
                            ->placeholder('17.4256')
                            ->helperText('Значение от -180 до 180 (например, для Нарвика: 17.4256).')
                            ->required()
                            ->rule('between:-180,180')
                            ->dehydrateStateUsing(fn ($state) => $state !== '' && $state !== null ? (float) $state : null),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Назначение исполнителя')
                    ->description('Кто будет выезжать на вызов: собственный помощник или внешний партнёр.')
                    ->schema([
                        Select::make('road_helper_id')
                            ->label('Дорожный помощник')
                            ->relationship('helper', 'id', function ($query) {
                                return $query->with('user');
                            })
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->user?->name ?? "Helper #{$record->id}"
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Оставьте пустым, если исполнителя будет назначать диспетчер позже.'),
                        Select::make('resolved_by_partner_id')
                            ->label('Партнёр')
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Выберите партнёра-эвакуатора, если кейс сразу передаётся внешнему подрядчику.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Статус заявки')
                    ->description('Текущее состояние экстренного вызова.')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'new' => 'Новый',
                                'assigned' => 'Назначен',
                                'on_route' => 'В пути',
                                'in_progress' => 'В работе',
                                'completed' => 'Завершен',
                                'failed' => 'Не удалось',
                                'cancelled' => 'Отменен',
                            ])
                            ->default('new')
                            ->required()
                            ->helperText('Обычно при создании оставляем «Новый» — дальше статус меняет диспетчер или автоматизация.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('incident_type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state = null): string => match ((string) $state) {
                        'jump_start' => '🔋 Прикуривание',
                        'fuel' => '⛽ Топливо',
                        'flat_tire' => '🛞 Прокол',
                        'locked_keys' => '🔑 Ключи',
                        'engine_no_start' => '🚗 Не заводится',
                        'tow_needed' => '🚛 Эвакуатор',
                        'accident' => '⚠️ ДТП',
                        default => (string) $state,
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->color(fn ($state = null): string => match ((string) $state) {
                        'new' => 'warning',
                        'assigned' => 'primary',
                        'on_route' => 'info',
                        'in_progress' => 'success',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state = null): string => match ((string) $state) {
                        'new' => 'Новый',
                        'assigned' => 'Назначен',
                        'on_route' => 'В пути',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершен',
                        'failed' => 'Не удалось',
                        'cancelled' => 'Отменен',
                        default => (string) $state,
                    })
                    ->sortable(),

                TextColumn::make('helper.user.name')
                    ->label('Помощник')
                    ->default('—')
                    ->searchable(),

                TextColumn::make('partner.name')
                    ->label('Партнёр')
                    ->default('—')
                    ->searchable(),

                TextColumn::make('location')
                    ->label('Координаты')
                    ->formatStateUsing(function ($record) {
                        if ($record->lat && $record->lng) {
                            return number_format($record->lat, 4).', '.number_format($record->lng, 4);
                        }

                        return '—';
                    })
                    ->url(fn ($record) => $record->lat && $record->lng
                        ? "https://www.google.com/maps?q={$record->lat},{$record->lng}"
                        : null
                    )
                    ->openUrlInNewTab(),

                TextColumn::make('photos')
                    ->label('Фото')
                    ->formatStateUsing(function ($record) {
                        if ($record->photos && count($record->photos) > 0) {
                            return '📷 '.count($record->photos);
                        }

                        return '—';
                    }),

                TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->default('—')
                    ->url(fn ($record) => $record->order_id
                        ? OrderResource::getUrl('edit', ['record' => $record->order_id])
                        : null
                    )
                    ->openUrlInNewTab()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новый',
                        'assigned' => 'Назначен',
                        'on_route' => 'В пути',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершен',
                        'failed' => 'Не удалось',
                        'cancelled' => 'Отменен',
                    ]),

                SelectFilter::make('incident_type')
                    ->label('Тип инцидента')
                    ->options([
                        'jump_start' => 'Прикуривание',
                        'fuel' => 'Закончилось топливо',
                        'flat_tire' => 'Прокол колеса',
                        'locked_keys' => 'Закрыты ключи',
                        'engine_no_start' => 'Не заводится',
                        'tow_needed' => 'Нужен эвакуатор',
                        'accident' => 'ДТП',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('create_order')
                    ->label('Создать заказ')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (RoadsideEmergency $record) => ! $record->order_id)
                    ->action(function (RoadsideEmergency $record) {
                        try {
                            DB::beginTransaction();

                            // Определяем тип услуги на основе типа инцидента
                            $serviceTypeCode = match ($record->incident_type) {
                                'tow_needed' => 'vehicle_transport',
                                default => 'roadside_assistance',
                            };

                            $serviceType = ServiceType::where('code', $serviceTypeCode)->first();

                            if (! $serviceType) {
                                throw new \Exception("Service type {$serviceTypeCode} not found. Please run RoadsideServiceTypesSeeder.");
                            }

                            // Определяем гео-зону по координатам (если есть)
                            $geoZone = null;
                            if ($record->lat && $record->lng) {
                                // Простая логика: найти ближайшую зону или первую активную
                                $geoZone = GeoZone::where('is_active', true)->first();
                            }

                            // Создаём Order
                            $order = Order::create([
                                'user_id' => $record->customer_id,
                                'status' => 'pending',
                                'geo_zone_id' => $geoZone?->id,
                                'location' => $record->lat && $record->lng ? [
                                    'lat' => $record->lat,
                                    'lng' => $record->lng,
                                ] : null,
                                'metadata' => [
                                    'created_from' => 'roadside_emergency',
                                    'roadside_emergency_id' => $record->id,
                                    'incident_type' => $record->incident_type,
                                ],
                            ]);

                            // Создаём OrderItem
                            OrderItem::create([
                                'order_id' => $order->id,
                                'service_type_id' => $serviceType->id,
                                'name' => $serviceType->name,
                                'description' => $record->incident_description,
                                'quantity' => 1,
                                'unit_price' => 0, // Цена будет рассчитана позже
                                'total_price' => 0,
                            ]);

                            // Привязываем RoadsideEmergency к Order
                            $record->order_id = $order->id;
                            $record->save();

                            DB::commit();

                            Notification::make()
                                ->title('Заказ создан')
                                ->body("Заказ #{$order->order_number} успешно создан и привязан к экстренному вызову")
                                ->success()
                                ->send();

                            return redirect(OrderResource::getUrl('edit', ['record' => $order]));
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Ошибка создания заказа')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('open_order')
                    ->label('Открыть заказ')
                    ->icon('heroicon-o-external-link')
                    ->color('primary')
                    ->visible(fn (RoadsideEmergency $record) => $record->order_id !== null)
                    ->url(fn (RoadsideEmergency $record) => $record->order_id
                        ? OrderResource::getUrl('edit', ['record' => $record->order_id])
                        : null
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListRoadsideEmergencies::route('/'),
            'create' => Pages\CreateRoadsideEmergency::route('/create'),
            'edit' => Pages\EditRoadsideEmergency::route('/{record}/edit'),
        ];
    }
}

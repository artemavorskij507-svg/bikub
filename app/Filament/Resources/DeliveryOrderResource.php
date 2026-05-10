<?php

namespace App\Filament\Resources;

use App\Enums\DeliveryTrackingStatus;
use App\Enums\DeliveryType;
use App\Enums\SubstitutionPolicy;
use App\Filament\Resources\DeliveryOrderResource\Pages;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\Delivery\CourierSelectorService;
use Closure;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Доставка';

    // TODO fixed by Cursor: normalize navigation group name to unified 'Операции'
    protected static ?string $navigationGroup = 'Операции';

    protected static ?int $navigationSort = 205;

    public static function getNavigationLabel(): string
    {
        return 'Delivery';
    }

    public static function getModelLabel(): string
    {
        return 'Delivery order';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Delivery orders';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основна інформація')
                    ->description('Базові параметри доставки та відповідальні.')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Замовлення')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    $set('delivery_address', null);
                                    $set('pickup_address', null);
                                    $set('type', null);
                                    $set('substitution_policy', null);

                                    return;
                                }
                                $order = Order::with('address')->find($state);
                                if (! $order) {
                                    return;
                                }
                                // Автоматично підставляємо тип доставки з service_type або metadata.
                                $serviceType = $order->service_type ?? ($order->metadata['delivery_type'] ?? null);
                                $mappedType = null;
                                if (is_string($serviceType)) {
                                    $lower = strtolower($serviceType);
                                    if (str_contains($lower, 'grocery')) {
                                        $mappedType = DeliveryType::GROCERY->value;
                                    } elseif (str_contains($lower, 'food')) {
                                        $mappedType = DeliveryType::FOOD->value;
                                    } elseif (str_contains($lower, 'bulky')) {
                                        $mappedType = DeliveryType::BULKY->value;
                                    }
                                }
                                if ($mappedType) {
                                    $set('type', $mappedType);
                                }
                                // Адреси з локації або повʼязаної адреси.
                                $location = is_array($order->location ?? null) ? $order->location : [];
                                $pickup = $location['pickup']['address'] ?? null;
                                $delivery = $location['delivery']['address'] ?? null;
                                if (! $delivery && $order->address) {
                                    $delivery = $order->address->formatted_address
                                        ?? trim(($order->address->street_address ?? '').' '.($order->address->city ?? ''));
                                }
                                if ($pickup) {
                                    $set('pickup_address', $pickup);
                                }
                                if ($delivery) {
                                    $set('delivery_address', $delivery);
                                }
                            })
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Тип доставки')
                            ->options([
                                DeliveryType::GROCERY->value => DeliveryType::GROCERY->label(),
                                DeliveryType::BULKY->value => DeliveryType::BULKY->label(),
                                DeliveryType::FOOD->value => DeliveryType::FOOD->label(),
                            ])
                            ->reactive()
                            ->default(DeliveryType::GROCERY->value)
                            ->required(),
                        Forms\Components\Select::make('tracking_status')
                            ->label('Статус відстеження')
                            ->options([
                                DeliveryTrackingStatus::PENDING->value => DeliveryTrackingStatus::PENDING->label(),
                                DeliveryTrackingStatus::ASSIGNED->value => DeliveryTrackingStatus::ASSIGNED->label(),
                                DeliveryTrackingStatus::PICKED_UP->value => DeliveryTrackingStatus::PICKED_UP->label(),
                                DeliveryTrackingStatus::IN_TRANSIT->value => DeliveryTrackingStatus::IN_TRANSIT->label(),
                                DeliveryTrackingStatus::DELIVERED->value => DeliveryTrackingStatus::DELIVERED->label(),
                                DeliveryTrackingStatus::CANCELLED->value => DeliveryTrackingStatus::CANCELLED->label(),
                            ])
                            ->default(DeliveryTrackingStatus::PENDING->value)
                            ->required(),
                        Forms\Components\Select::make('courier_id')
                            ->label('Кур\'єр')
                            ->relationship('courier', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Можна залишити порожнім — курʼєра можна призначити пізніше.'),
                        Forms\Components\Toggle::make('is_urgent')
                            ->label('Термінове')
                            ->default(false),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Маршрут')
                    ->schema([
                        Forms\Components\Textarea::make('pickup_address')
                            ->label('Адреса забирання')
                            ->rows(2)
                            ->placeholder('Звідки забираємо замовлення'),
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Адреса доставки')
                            ->rows(2)
                            ->required()
                            ->placeholder('Куди доставляємо замовлення'),
                        Forms\Components\DateTimePicker::make('eta')
                            ->label('Очікуваний час доставки')
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Якщо залишити порожнім, ETA буде розраховано автоматично, коли зʼявляться координати.')
                            ->nullable(),
                        Forms\Components\Select::make('substitution_policy')
                            ->label('Політика замін')
                            ->options([
                                SubstitutionPolicy::STRICT->value => SubstitutionPolicy::STRICT->label(),
                                SubstitutionPolicy::AI->value => SubstitutionPolicy::AI->label(),
                                SubstitutionPolicy::CONTACT->value => SubstitutionPolicy::CONTACT->label(),
                            ])
                            ->visible(fn (Closure $get) => $get('type') === DeliveryType::GROCERY->value)
                            ->nullable()
                            ->helperText('Застосовується для продуктів, коли немає товару в наявності.'),
                    ])
                    ->columns(2),
                // Dynamic fields based on type
                Forms\Components\Section::make('Деталі замовлення за типом')
                    ->schema(fn (Closure $get) => match ($get('type')) {
                        DeliveryType::GROCERY->value => self::getGroceryFields(),
                        DeliveryType::BULKY->value => self::getBulkyFields(),
                        DeliveryType::FOOD->value => self::getFoodFields(),
                        default => [],
                    })
                    ->visible(fn (Closure $get) => in_array($get('type'), [
                        DeliveryType::GROCERY->value,
                        DeliveryType::BULKY->value,
                        DeliveryType::FOOD->value,
                    ]))
                    ->columns(2),
            ]);
    }

    protected static function getGroceryFields(): array
    {
        return [
            Forms\Components\Select::make('store_id')
                ->label('Магазин')
                ->options(\App\Models\RetailStore::where('category', 'grocery')->active()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Виберіть магазин для замовлення продуктів'),
        ];
    }

    protected static function getBulkyFields(): array
    {
        return [
            Forms\Components\TextInput::make('dimensions.length')
                ->label('Довжина (см)')
                ->numeric()
                ->nullable(),
            Forms\Components\TextInput::make('dimensions.width')
                ->label('Ширина (см)')->numeric()
                ->nullable(),
            Forms\Components\TextInput::make('dimensions.height')
                ->label('Висота (см)')
                ->numeric()
                ->nullable(),
            Forms\Components\TextInput::make('weight_kg')
                ->label('Вага (кг)')
                ->numeric()
                ->nullable(),
            Forms\Components\CheckboxList::make('services')
                ->label('Додаткові послуги')
                ->options([
                    'assembly' => 'Збірка',
                    'disassembly' => 'Розбірка',
                    'packaging' => 'Упаковка',
                    'wrapping' => 'Обгортання',
                ])
                ->columns(2),
            Forms\Components\TextInput::make('floor_number')
                ->label('Поверх')
                ->numeric()
                ->nullable(),
            Forms\Components\Toggle::make('elevator_available')
                ->label('Є ліфт')
                ->default(false),
        ];
    }

    protected static function getFoodFields(): array
    {
        return [
            Forms\Components\Select::make('restaurant_id')
                ->label('Ресторан')
                ->options(\App\Models\Restaurant::pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Виберіть ресторан для замовлення їжі'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Номер замовлення')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => DeliveryType::from($state)->label())
                    ->colors([
                        'primary' => 'grocery',
                        'success' => 'food',
                        'warning' => 'bulky',
                    ]),
                Tables\Columns\TextColumn::make('orderable.store.name')
                    ->label('Магазин')
                    ->sortable()
                    ->toggleable()
                    ->default('—')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record || $record->orderable_type !== \App\Models\Delivery\GroceryOrder::class) {
                            return '—';
                        }

                        return $state ?? '—';
                    }),
                Tables\Columns\BadgeColumn::make('tracking_status')
                    ->label('Статус')
                    ->colors([
                        'secondary' => 'pending',
                        'info' => 'assigned',
                        'warning' => 'picked_up',
                        'primary' => 'in_transit',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('courier.email')
                    ->label('Кур\'єр')
                    ->searchable()
                    ->default('Не призначено'),
                Tables\Columns\TextColumn::make('delivery_address')
                    ->label('Адреса доставки')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->delivery_address),
                Tables\Columns\TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('Термінове')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип доставки')
                    ->options([
                        DeliveryType::GROCERY->value => DeliveryType::GROCERY->label(),
                        DeliveryType::BULKY->value => DeliveryType::BULKY->label(),
                        DeliveryType::FOOD->value => DeliveryType::FOOD->label(),
                    ]),
                Tables\Filters\SelectFilter::make('tracking_status')
                    ->label('Статус')
                    ->options([
                        DeliveryTrackingStatus::PENDING->value => DeliveryTrackingStatus::PENDING->label(),
                        DeliveryTrackingStatus::ASSIGNED->value => DeliveryTrackingStatus::ASSIGNED->label(),
                        DeliveryTrackingStatus::PICKED_UP->value => DeliveryTrackingStatus::PICKED_UP->label(),
                        DeliveryTrackingStatus::IN_TRANSIT->value => DeliveryTrackingStatus::IN_TRANSIT->label(),
                        DeliveryTrackingStatus::DELIVERED->value => DeliveryTrackingStatus::DELIVERED->label(),
                        DeliveryTrackingStatus::CANCELLED->value => DeliveryTrackingStatus::CANCELLED->label(),
                    ]),
                Tables\Filters\Filter::make('is_urgent')
                    ->label('Термінові')
                    ->query(fn (Builder $query) => $query->where('is_urgent', true)),
                Tables\Filters\Filter::make('has_courier')
                    ->label('С курьером')
                    ->query(fn (Builder $query) => $query->whereNotNull('courier_id')),
                Tables\Filters\Filter::make('created_at')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('eta')
                    ->label('ETA')
                    ->form([
                        Forms\Components\DatePicker::make('eta_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('eta_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['eta_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('eta', '>=', $date),
                            )
                            ->when(
                                $data['eta_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('eta', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assignCourier')
                    ->label('Назначить кур\'єра')
                    ->icon('heroicon-o-user')
                    ->visible(fn (DeliveryOrder $record) => Auth::user()?->can('update', $record) ?? false)
                    ->form([
                        Forms\Components\Select::make('courier_id')
                            ->label('Кур\'єр')
                            ->options(fn () => User::couriers()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data, DeliveryOrder $record) {
                        $record->courier_id = $data['courier_id'];
                        if ($record->tracking_status === DeliveryTrackingStatus::PENDING) {
                            $record->tracking_status = DeliveryTrackingStatus::ASSIGNED;
                        }
                        $record->save();
                        Notification::make()
                            ->title('Кур\'єра призначено')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('autoAssignCourier')
                    ->label('Авто-назначити')
                    ->icon('heroicon-o-sparkles')
                    ->visible(fn (DeliveryOrder $record) => Auth::user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (DeliveryOrder $record) {
                        if ($record->courier_id) {
                            Notification::make()
                                ->title('Кур\'єр вже призначений')
                                ->warning()
                                ->send();

                            return;
                        }
                        /** @var CourierSelectorService $selector */
                        $selector = app(CourierSelectorService::class);
                        $courier = $selector->findForDelivery($record);
                        if (! $courier) {
                            Notification::make()
                                ->title('Немає доступних кур\'єрів')
                                ->warning()
                                ->send();

                            return;
                        }
                        $record->courier_id = $courier->id;
                        if ($record->tracking_status === DeliveryTrackingStatus::PENDING) {
                            $record->tracking_status = DeliveryTrackingStatus::ASSIGNED;
                        }
                        $record->save();
                        Notification::make()
                            ->title("Кур\'єр {$courier->name} призначений")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'delivery_orders_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Номер', 'Тип', 'Статус', 'Курьер', 'Адрес доставки', 'ETA', 'Срочное', 'Создано']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->order->order_number ?? '—',
                                    $record->type,
                                    $record->tracking_status,
                                    $record->courier->email ?? 'Не назначен',
                                    $record->delivery_address,
                                    $record->eta ? $record->eta->format('Y-m-d H:i:s') : '—',
                                    $record->is_urgent ? 'Да' : 'Нет',
                                    $record->created_at->format('Y-m-d H:i:s'),
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('assign_courier')
                    ->label('Назначить курьера')
                    ->icon('heroicon-o-user')
                    ->form([
                        Forms\Components\Select::make('courier_id')
                            ->label('Курьер')
                            ->options(fn () => User::couriers()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        $count = 0;
                        foreach ($records as $record) {
                            if (! $record->courier_id) {
                                $record->courier_id = $data['courier_id'];
                                if ($record->tracking_status === DeliveryTrackingStatus::PENDING) {
                                    $record->tracking_status = DeliveryTrackingStatus::ASSIGNED;
                                }
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title('Курьеры назначены')
                            ->body("Обновлено заказов: {$count}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('change_status')
                    ->label('Изменить статус')
                    ->icon('heroicon-o-refresh')
                    ->form([
                        Forms\Components\Select::make('tracking_status')
                            ->label('Новый статус')
                            ->options([
                                DeliveryTrackingStatus::PENDING->value => DeliveryTrackingStatus::PENDING->label(),
                                DeliveryTrackingStatus::ASSIGNED->value => DeliveryTrackingStatus::ASSIGNED->label(),
                                DeliveryTrackingStatus::PICKED_UP->value => DeliveryTrackingStatus::PICKED_UP->label(),
                                DeliveryTrackingStatus::IN_TRANSIT->value => DeliveryTrackingStatus::IN_TRANSIT->label(),
                                DeliveryTrackingStatus::DELIVERED->value => DeliveryTrackingStatus::DELIVERED->label(),
                                DeliveryTrackingStatus::CANCELLED->value => DeliveryTrackingStatus::CANCELLED->label(),
                            ])
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $record->update(['tracking_status' => $data['tracking_status']]);
                        }
                        Notification::make()
                            ->title('Статус изменен')
                            ->body('Обновлено заказов: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('mark_urgent')
                    ->label('Пометить срочными')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['is_urgent' => true]);
                        }
                        Notification::make()
                            ->title('Заказы помечены срочными')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            'view' => Pages\ViewDeliveryOrder::route('/{record}'),
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
        ];
    }
}

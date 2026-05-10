<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleInspectionRequestResource\Pages;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceType;
use App\Models\VehicleInspectionRequest;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;

class VehicleInspectionRequestResource extends Resource
{
    protected static ?string $model = VehicleInspectionRequest::class;

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static bool $shouldRegisterNavigation = true;

    // fix: unify Roadside module icon for consistent navigation
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Заявки на осмотр авто';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 605;

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

    // fix: relax access to avoid 403 and hiding from navigation (visible to any authenticated user)
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Клиент и авто')
                    ->description('Кто заказывает осмотр и какой автомобиль нужно проверить.')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Клиент')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Выберите клиента, для которого создаётся заявка на осмотр авто.'),
                        Forms\Components\Select::make('preset_id')
                            ->label('Пресет осмотра')
                            ->relationship('preset', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Шаблон чек-листа осмотра. Можно оставить пустым и выбрать позже.'),
                        Forms\Components\TextInput::make('vehicle_make')
                            ->label('Марка авто')
                            ->maxLength(100)
                            ->placeholder('Например: Toyota, VW, BMW')
                            ->nullable(),
                        Forms\Components\TextInput::make('vehicle_model')
                            ->label('Модель авто')
                            ->maxLength(100)
                            ->placeholder('Например: Corolla, Transporter, X5')
                            ->nullable(),
                        Forms\Components\TextInput::make('vehicle_year')
                            ->label('Год выпуска')
                            ->numeric()
                            ->minValue(1950)
                            ->maxValue(now()->year + 1)
                            ->nullable(),
                        Forms\Components\TextInput::make('vin_code')
                            ->label('VIN авто')
                            ->maxLength(32)
                            ->placeholder('17-значный VIN, если известен')
                            ->nullable(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Продавец и место осмотра')
                    ->description('Контактные данные продавца и адрес, где нужно провести осмотр.')
                    ->schema([
                        Forms\Components\TextInput::make('seller_name')
                            ->label('Продавец')
                            ->maxLength(255)
                            ->placeholder('Имя продавца или салона')
                            ->nullable(),
                        Forms\Components\TextInput::make('seller_phone')
                            ->label('Телефон продавца')
                            ->tel()
                            ->maxLength(50)
                            ->placeholder('+47 ...')
                            ->nullable(),
                        Forms\Components\Textarea::make('address')
                            ->label('Адрес осмотра')
                            ->rows(3)
                            ->placeholder('Улица, дом, индекс, город')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('requested_time')
                            ->label('Желаемое время осмотра')
                            ->nullable()
                            ->helperText('Ориентировочное время выезда инспектора. Можно скорректировать позже.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Назначение и статус')
                    ->description('Кто будет выполнять осмотр и какой у заявки статус.')
                    ->schema([
                        Forms\Components\Select::make('assigned_helper_id')->label('Назначенный инспектор')
                            ->relationship('helper', 'id')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->user?->name ?? 'Helper #'.$record->id
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Можно оставить пустым, чтобы назначить инспектора позже через диспетчерскую доску.'),
                        Forms\Components\Select::make('status')
                            ->label('Статус заявки')
                            ->options([
                                'new' => 'Новая',
                                'assigned' => 'Назначена',
                                'in_progress' => 'В работе',
                                'finished' => 'Завершена',                                'cancelled' => 'Отменена',
                            ])
                            ->default('new')
                            ->required()
                            ->helperText('Используется для контроля потока заявок и SLA.'),
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

                TextColumn::make('preset.title')
                    ->label('Пресет')
                    ->searchable(),

                TextColumn::make('vehicle_make')
                    ->label('Марка')
                    ->searchable(),

                TextColumn::make('vehicle_model')
                    ->label('Модель')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->color(fn ($state = null): string => match ((string) $state) {
                        'new' => 'warning',
                        'assigned' => 'primary',
                        'in_progress' => 'info',
                        'finished' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state = null): string => match ((string) $state) {
                        'new' => 'Новый',
                        'assigned' => 'Назначен',
                        'in_progress' => 'В работе',
                        'finished' => 'Завершен',
                        'cancelled' => 'Отменен',
                        default => (string) $state,
                    })
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новый',
                        'assigned' => 'Назначен',
                        'in_progress' => 'В работе',
                        'finished' => 'Завершен',
                        'cancelled' => 'Отменен',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('create_order')
                    ->label('Создать заказ')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (VehicleInspectionRequest $record) => ! $record->order_id)
                    ->action(function (VehicleInspectionRequest $record) {
                        try {
                            DB::beginTransaction();

                            // fix: ensure service type exists (auto-create if missing)
                            $serviceType = ServiceType::firstOrCreate(
                                ['code' => 'vehicle_inspection'],
                                [
                                    'name' => 'Осмотр авто',
                                    'slug' => 'vehicle_inspection',
                                ]
                            );

                            // Определяем гео-зону
                            $geoZone = GeoZone::where('is_active', true)->first();

                            // Создаём Order
                            $order = Order::create([
                                'user_id' => $record->customer_id,
                                'status' => 'pending',                                'geo_zone_id' => $geoZone?->id,
                                'location' => $record->address ? [
                                    'address' => $record->address,
                                ] : null,
                                'metadata' => [
                                    'created_from' => 'vehicle_inspection_request',
                                    'vehicle_inspection_request_id' => $record->id,
                                    'preset_id' => $record->preset_id,
                                    'vehicle_make' => $record->vehicle_make,
                                    'vehicle_model' => $record->vehicle_model,
                                    'vehicle_year' => $record->vehicle_year,                                ],
                            ]);

                            // Создаём OrderItem
                            OrderItem::create(['order_id' => $order->id,
                                'service_type_id' => $serviceType->id,
                                'name' => $record->preset->title ?? $serviceType->name,
                                'description' => $record->preset->description ?? null,
                                'quantity' => 1,
                                'unit_price' => $record->preset->price ?? 0,
                                'total_price' => $record->preset->price ?? 0,
                            ]);

                            // Привязываем VehicleInspectionRequest к Order
                            $record->order_id = $order->id;
                            $record->save();

                            DB::commit();

                            Notification::make()
                                ->title('Заказ создан')
                                ->body("Заказ #{$order->order_number} успешно создан и привязан к заявке на осмотр")
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
                    ->visible(fn (VehicleInspectionRequest $record) => $record->order_id !== null)
                    ->url(fn (VehicleInspectionRequest $record) => $record->order_id
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
            'index' => Pages\ListVehicleInspectionRequests::route('/'),
            'create' => Pages\CreateVehicleInspectionRequest::route('/create'),
            'edit' => Pages\EditVehicleInspectionRequest::route('/{record}/edit'),
        ];
    }
}

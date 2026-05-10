<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ErrandTaskResource\Pages;
use App\Models\ErrandTask;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ErrandTaskResource extends Resource
{
    protected static ?string $model = ErrandTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Delivery';

    protected static ?int $navigationSort = 201;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Быстрое поручение с привязкой к заказу.')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Заказ')
                            ->relationship('order', 'order_number')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if (empty($state)) {
                                    // Очистка связанных полей, если заказ снят.
                                    $set('customer_name', null);
                                    $set('customer_phone', null);
                                    $set('pickup_address', null);
                                    $set('dropoff_address', null);
                                    $set('category', null);
                                    $set('description', null);
                                    $set('is_urgent', false);
                                    $set('requires_signature', false);
                                    $set('requires_trusted_helper', false);
                                    $set('scheduled_at', null);

                                    return;
                                }
                                $order = Order::with(['user', 'errandDetails'])->find($state);
                                if (! $order) {
                                    return;
                                }
                                // Клиент
                                $set('customer_name', $order->user->name ?? null);
                                if (property_exists($order->user, 'phone')) {
                                    $set('customer_phone', $order->user->phone ?? null);
                                }
                                // Детали поручения из errandDetails
                                $details = $order->errandDetails;
                                if ($details) {
                                    $set('category', $details->category ?? null);
                                    if (! empty($details->description)) {
                                        $set('description', $details->description);
                                    }
                                    $set('pickup_address', $details->from_address ?? null);
                                    $set('dropoff_address', $details->to_address ?? null);
                                    $set('is_urgent', (bool) ($details->is_urgent ?? false));
                                    $set('requires_signature', (bool) ($details->requires_signature ?? false));
                                    $set('requires_trusted_helper', (bool) ($details->requires_trusted_helper ?? false));
                                    if (! empty($details->desired_start_at)) {
                                        $set('scheduled_at', $details->desired_start_at);
                                    }
                                }
                            })
                            ->helperText('Выберите существующий заказ, к которому относится поручение.'),
                        Forms\Components\TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Забрать документы из NAV и доставить клиенту')
                            ->helperText('Кратко и понятно опишите суть поручения.'),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'purchase_and_deliver' => 'Купить и доставить',
                                'pickup_and_drop' => 'Забрать и передать',
                                'document_service' => 'Документы и госуслуги',
                                'pharmacy' => 'Аптека / лекарства',
                                'special_errand' => 'Особые поручения',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->placeholder('Детальное описание: что купить/забрать, где, у кого, особые инструкции...'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'pending' => 'Ожидает',
                                'assigned' => 'Назначено',
                                'in_progress' => 'В работе',
                                'completed' => 'Завершено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('draft'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Клиент и адреса')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Имя клиента')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Телефон клиента')
                            ->tel()->maxLength(32),
                        Forms\Components\TextInput::make('pickup_address')
                            ->label('Адрес забора')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('dropoff_address')->label('Адрес доставки')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Исполнение и SLA')
                    ->schema([
                        Forms\Components\Toggle::make('is_urgent')
                            ->label('Срочное поручение')
                            ->helperText('Помечает задачу как приоритетную для диспетчеров.'),
                        Forms\Components\Toggle::make('requires_signature')
                            ->label('Требуется подпись'),
                        Forms\Components\Toggle::make('requires_trusted_helper')
                            ->label('Нужен доверенный помощник'),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Запланировано на')
                            ->helperText('Желаемое время выполнения поручения.'),
                        Forms\Components\TextInput::make('expected_distance_km')
                            ->label('Оценочная дистанция (км)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.1'),
                        Forms\Components\TextInput::make('expected_duration_minutes')
                            ->label('Оценочная длительность (мин)')
                            ->numeric()
                            ->minValue(0)
                            ->step(5),
                        Forms\Components\Select::make('executor_profile_id')
                            ->label('Исполнитель')
                            ->options(function () {
                                return \App\Models\Moving\ExecutorProfile::with('user')
                                    ->get()
                                    ->mapWithKeys(function ($profile) {
                                        $label = $profile->user?->name ?? "Executor #{$profile->id}";

                                        return [$profile->id => $label];
                                    });
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Moving\ExecutorProfile::with('user')
                                    ->whereHas('user', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($profile) {
                                        $label = $profile->user?->name ?? "Executor #{$profile->id}";

                                        return [$profile->id => $label];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Можно оставить пустым, чтобы назначить исполнителя позже.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Финансы (опционально)')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('material_advance_amount')
                            ->label('Аванс на материалы (NOK)')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('base_fee')
                            ->label('Базовый сбор (NOK)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01'),
                        Forms\Components\TextInput::make('distance_fee')
                            ->label('Доплата за расстояние (NOK)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01'),
                        Forms\Components\TextInput::make('time_fee')
                            ->label('Доплата за время (NOK)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01'),
                        Forms\Components\TextInput::make('urgency_fee')
                            ->label('Срочная доплата (NOK)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01'),
                        Forms\Components\TextInput::make('estimated_total_amount')
                            ->label('Оценочная сумма (NOK)')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Внутренние заметки диспетчера')
                            ->rows(3)
                            ->helperText('Эти заметки видны только в админ-панели и не отображаются клиенту.'),
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
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->sortable()
                    ->searchable()
                    ->default('—')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap()
                    ->default('—')
                    ->placeholder('—'),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Категория')
                    ->getStateUsing(fn ($record) => $record->category ?? 'unknown')
                    ->colors([
                        'primary' => 'purchase_and_deliver',
                        'info' => 'pickup_and_drop',
                        'success' => 'document_service',
                        'warning' => 'pharmacy',
                        'danger' => 'special_errand',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'purchase_and_deliver' => 'Купить и доставить',
                        'pickup_and_drop' => 'Забрать и передать',
                        'document_service' => 'Документы и госуслуги',
                        'pharmacy' => 'Аптека / лекарства',
                        'special_errand' => 'Особые поручения',
                        default => $state ?? '—',
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->getStateUsing(fn ($record) => $record->status ?? 'draft')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => ['pending', 'urgent'],
                        'info' => 'assigned',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'draft' => 'Черновик',
                        'pending' => 'Ожидает',
                        'urgent' => 'Срочно',
                        'assigned' => 'Назначено',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                        default => $state ?? '—',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('executorProfile.user.name')
                    ->label('Исполнитель')
                    ->sortable()
                    ->searchable()
                    ->default('— не назначен —')
                    ->placeholder('— не назначен —'),
                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('Срочно')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation'),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('pickup_address')
                    ->label('Откуда')
                    ->limit(30)
                    ->wrap()
                    ->toggleable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('dropoff_address')
                    ->label('Куда')
                    ->limit(30)
                    ->wrap()
                    ->toggleable()->default('—'),
                Tables\Columns\TextColumn::make('estimated_total_amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state) => ($state !== null && $state !== '') ? number_format((float) $state, 2, ',', ' ').' NOK' : '—')
                    ->sortable()
                    ->toggleable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Запланировано')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }
                        try {
                            if ($state instanceof \Carbon\Carbon) {
                                return $state->format('d.m.Y H:i');
                            }

                            return \Carbon\Carbon::parse($state)->format('d.m.Y H:i');
                        } catch (\Exception $e) {
                            return '—';
                        }
                    })
                    ->sortable()
                    ->toggleable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }
                        try {
                            if ($state instanceof \Carbon\Carbon) {
                                return $state->format('d.m.Y H:i');
                            }

                            return \Carbon\Carbon::parse($state)->format('d.m.Y H:i');
                        } catch (\Exception $e) {
                            return '—';
                        }
                    })
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options([
                        'purchase_and_deliver' => 'Купить и доставить',
                        'pickup_and_drop' => 'Забрать и передать',
                        'document_service' => 'Документы и госуслуги',
                        'pharmacy' => 'Аптека / лекарства',
                        'special_errand' => 'Особые поручения',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'pending' => 'Ожидает',
                        'assigned' => 'Назначено',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершено',
                        'cancelled' => 'Отменено',
                    ]),
                Tables\Filters\TernaryFilter::make('is_urgent')
                    ->label('Срочный заказ'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListErrandTasks::route('/'),
            'create' => Pages\CreateErrandTask::route('/create'),
            'edit' => Pages\EditErrandTask::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\ServiceType;
use App\Events\ClaimRejected;
use App\Events\ClaimResolved;
use App\Filament\Resources\ClaimResource\Pages;
use App\Models\Claim;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 401;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Claim::class) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Клиент и контекст')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Клиент')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => trim(($record->name ?? '').' '.($record->email ?? ''))
                                    ?: ($record->email ?? "Пользователь #{$record->id}")
                            )
                            ->preload()
                            ->searchable()
                            ->required()
                            ->helperText('Кто подаёт претензию. Можно выбрать вручную или автоматически из заказа.'),
                        Forms\Components\Select::make('order_id')
                            ->label('Заказ')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if (empty($state)) {
                                    return;
                                }

                                $order = Order::with('user')->find($state);
                                if (! $order) {
                                    return;
                                }

                                // Подставляем клиента из заказа.
                                if ($order->user_id) {
                                    $set('user_id', $order->user_id);
                                }

                                // Заполняем заголовок по умолчанию, если он пуст.
                                $currentTitle = (string) ($get('title') ?? '');
                                if ($currentTitle === '') {
                                    $fallbackNumber = $order->order_number ?? $order->id;
                                    $set('title', $fallbackNumber ? 'Претензия по заказу #'.$fallbackNumber : 'Претензия по заказу');
                                }
                            })
                            ->helperText('Опционально: свяжите претензию с конкретным заказом.'),
                        Forms\Components\Select::make('repair_project_id')
                            ->label('Проект ремонта')
                            ->relationship('repairProject', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Заполните, если претензия связана с проектом ремонта.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Тип и содержание')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'quality' => 'Качество',
                                'damage' => 'Повреждение',
                                'delay' => 'Задержка',
                                'billing' => 'Оплата',
                                'other' => 'Другое',
                            ])
                            ->required(),
                        Forms\Components\Select::make('severity')
                            ->label('Критичность')
                            ->options([
                                'low' => 'Низкая',
                                'medium' => 'Средняя',
                                'high' => 'Высокая',
                            ])
                            ->default('medium')
                            ->nullable(),
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->placeholder('Краткое описание проблемы'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->required()
                            ->helperText('Подробно опишите ситуацию, ожидания клиента и что пошло не так.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ответственные и статус')
                    ->schema([
                        Forms\Components\Select::make('opened_by_user_id')
                            ->label('Инициатор')
                            ->relationship('openedBy', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => trim(($record->name ?? '').' '.($record->email ?? ''))
                                    ?: ($record->email ?? "Пользователь #{$record->id}")
                            )
                            ->preload()
                            ->searchable()
                            ->default(fn () => auth()->id())
                            ->nullable()
                            ->helperText('Кто зарегистрировал претензию (по умолчанию текущий пользователь).'),
                        Forms\Components\Select::make('assigned_to_user_id')
                            ->label('Ответственный')
                            ->relationship('assignedTo', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => trim(($record->name ?? '').' '.($record->email ?? ''))
                                    ?: ($record->email ?? "Пользователь #{$record->id}")
                            )
                            ->preload()
                            ->searchable()
                            ->nullable()
                            ->helperText('Кто отвечает за разбор претензии. Можно назначить позже.'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'open' => 'Открыта',
                                'in_review' => 'На рассмотрении',
                                'resolved' => 'Решена',
                                'rejected' => 'Отклонена',
                                'closed' => 'Закрыта',
                            ])
                            ->default('open')
                            ->required(),
                        Forms\Components\DateTimePicker::make('opened_at')
                            ->label('Дата открытия')
                            ->default(fn () => now())
                            ->helperText('Когда претензия была зарегистрирована.'),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Дата решения')
                            ->nullable()
                            ->helperText('Заполнится при закрытии претензии.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Решение')
                    ->schema([
                        Forms\Components\Select::make('resolution_type')
                            ->label('Тип решения')
                            ->options([
                                'partial_refund' => 'Частичный возврат',
                                'full_refund' => 'Полный возврат',
                                'redo_work' => 'Переделать работы',
                                'goodwill' => 'Жест доброй воли',
                                'no_action' => 'Без действий',
                            ])
                            ->nullable(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Решение / комментарий клиенту')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\KeyValue::make('meta')
                            ->label('Meta')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->nullable()
                            ->helperText('Служебные данные для интеграций и аналитики.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('order.service_type')
                    ->label('Тип заказа')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }
                        $serviceType = ServiceType::tryFrom($state);

                        return $serviceType ? $serviceType->label() : $state;
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('repairProject.title')
                    ->label('Проект ремонта')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'primary',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'quality' => 'Качество',
                            'damage' => 'Повреждение',
                            'delay' => 'Задержка',
                            'billing' => 'Оплата',
                            'other' => 'Другое',
                            default => $state,
                        };
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'open',
                        'info' => 'in_review',
                        'success' => 'resolved',
                        'danger' => 'rejected',
                        'secondary' => 'closed',
                    ])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Критичность')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low' => 'Низкая',
                        'medium' => 'Средняя',
                        'high' => 'Высокая',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Претензия')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Открыта')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Решена')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sla_response_due_at')
                    ->label('SLA ответ до')
                    ->dateTime('d.m H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('sla_response_breached')
                    ->label('Ответ SLA')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-check-circle')
                    ->colors([
                        'danger' => fn ($state) => $state === true,
                        'success' => fn ($state) => $state === false,
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sla_resolution_due_at')
                    ->label('SLA решение до')
                    ->dateTime('d.m H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('sla_resolution_breached')
                    ->label('Решение SLA')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-check-circle')
                    ->colors([
                        'danger' => fn ($state) => $state === true,
                        'success' => fn ($state) => $state === false,
                    ])
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'open' => 'Открыта',
                        'in_review' => 'На рассмотрении',
                        'resolved' => 'Решена',
                        'rejected' => 'Отклонена',
                        'closed' => 'Закрыта',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'quality' => 'Качество',
                        'damage' => 'Повреждение',
                        'delay' => 'Задержка',
                        'billing' => 'Оплата',
                        'other' => 'Другое',
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Критичность')
                    ->options([
                        'low' => 'Низкая',
                        'medium' => 'Средняя',
                        'high' => 'Высокая',
                    ]),
                Tables\Filters\Filter::make('service_type')
                    ->label('Тип услуги')
                    ->form([
                        Forms\Components\Select::make('service_type')
                            ->label('Тип услуги')
                            ->options([
                                ServiceType::HANDYMAN_HOURLY->value => ServiceType::HANDYMAN_HOURLY->label(),
                                ServiceType::HANDYMAN_FIXED->value => ServiceType::HANDYMAN_FIXED->label(),
                                ServiceType::COMPLEX_REPAIR->value => ServiceType::COMPLEX_REPAIR->label(),
                                ServiceType::GROCERY_DELIVERY->value => ServiceType::GROCERY_DELIVERY->label(),
                            ])
                            ->multiple(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['service_type'],
                            fn ($query, $types) => $query->whereHas('order', fn ($q) => $q->whereIn('service_type', $types))
                        );
                    }),
                Tables\Filters\Filter::make('sla_response_breached')
                    ->label('Нарушен SLA по ответу')
                    ->query(fn ($q) => $q->where('sla_response_breached', true)),
                Tables\Filters\Filter::make('sla_resolution_breached')
                    ->label('Нарушен SLA по решению')
                    ->query(fn ($q) => $q->where('sla_resolution_breached', true)),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Закрыть как решённую')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Claim $record) => in_array($record->status, ['open', 'in_review']))
                    ->form([
                        Forms\Components\Select::make('resolution_type')
                            ->label('Тип решения')
                            ->options([
                                'partial_refund' => 'Частичный возврат',
                                'full_refund' => 'Полный возврат',
                                'redo_work' => 'Переделка работ',
                                'goodwill' => 'Жест доброй воли',
                                'no_action' => 'Без действий',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Комментарий')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, Claim $record) {
                        $record->update([
                            'status' => 'resolved',
                            'resolution_type' => $data['resolution_type'],
                            'resolution_notes' => $data['resolution_notes'],
                            'resolved_at' => now(),
                        ]);
                        $record->refresh();
                        event(new ClaimResolved($record));
                        // TODO: завязать на финансовую систему: частичный/полный возврат, скидка и т.п.
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить претензию')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Claim $record) => in_array($record->status, ['open', 'in_review']))
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Комментарий')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, Claim $record) {
                        $record->update([
                            'status' => 'rejected',
                            'resolution_type' => 'no_action',
                            'resolution_notes' => $data['resolution_notes'],
                            'resolved_at' => now(),
                        ]);
                        $record->refresh();
                        event(new ClaimRejected($record));
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('opened_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClaims::route('/'),
            'create' => Pages\CreateClaim::route('/create'),
            'edit' => Pages\EditClaim::route('/{record}/edit'),
        ];
    }
}

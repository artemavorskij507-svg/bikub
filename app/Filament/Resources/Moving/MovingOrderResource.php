<?php

namespace App\Filament\Resources\Moving;

use App\Filament\Resources\Moving\MovingOrderResource\Pages;
use App\Filament\Resources\Moving\MovingOrderResource\RelationManagers;
use App\Models\Moving\MovingOrder;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class MovingOrderResource extends Resource
{
    protected static ?string $model = MovingOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Moving';

    protected static ?int $navigationSort = 503;

    protected static ?string $modelLabel = 'замовлення переїзду';

    protected static ?string $pluralModelLabel = 'замовлення переїздів';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Маршрут')
                        ->icon('heroicon-o-map')
                        ->schema([
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required()
                                ->label('Клієнт'),
                            Grid::make(2)
                                ->schema([
                                    Fieldset::make('Звідки')
                                        ->schema(self::addressSchema())
                                        ->columns(2)
                                        ->statePath('from_address'),
                                    Fieldset::make('Куди')
                                        ->schema(self::addressSchema())
                                        ->columns(2)
                                        ->statePath('to_address'),
                                ]),
                            Grid::make()
                                ->schema([
                                    Select::make('package_type')
                                        ->label('Пакет послуг')
                                        ->options(self::packageOptions())
                                        ->default('standard')
                                        ->required(),
                                    Select::make('status')
                                        ->label('Статус')
                                        ->options(self::statusOptions())
                                        ->default('pending')
                                        ->required(),
                                    DateTimePicker::make('scheduled_at')
                                        ->label('Час виконання')
                                        ->minDate(now())
                                        ->required()
                                        ->helperText('Мінімальна дата - сьогодні'),
                                ])->columns(3),
                        ]),
                    Step::make('Майно')
                        ->icon('heroicon-o-cube')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->columnSpan('full')
                                ->createItemButtonLabel('Додати предмет')
                                ->grid(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Назва')
                                        ->required(),
                                    TextInput::make('category')
                                        ->label('Категорія')
                                        ->required(),
                                    TextInput::make('volume')
                                        ->label('Обʼєм, м³')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->required()
                                        ->helperText('Обʼєм одного предмета'),
                                    TextInput::make('weight')
                                        ->label('Вага, кг')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->required()
                                        ->helperText('Вага одного предмета'),
                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->label('Кількість')
                                        ->required()
                                        ->helperText('Кількість однакових предметів'),
                                    Toggle::make('requires_assembly')
                                        ->label('Потребує збірки'),
                                    Toggle::make('is_fragile')
                                        ->label('Крихкий предмет'),
                                    Textarea::make('notes')
                                        ->columnSpanFull()
                                        ->label('Нотатки'),
                                ]),
                        ]),
                    Step::make('Послуги')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            CheckboxList::make('services')
                                ->label('Додаткові послуги')
                                ->options(self::serviceOptions())
                                ->columns(2)
                                ->afterStateHydrated(function ($component, $state): void {
                                    if (is_array($state)) {
                                        $component->state(collect($state)->filter()->keys()->all());
                                    }
                                })
                                ->dehydrateStateUsing(fn (?array $state) => collect($state ?? [])->mapWithKeys(fn ($value) => [$value => true])->all()),
                            Textarea::make('customer_notes')
                                ->label('Коментар клієнта')
                                ->columnSpan('full'),
                        ]),
                    Step::make('Ціни та бригада')
                        ->icon('heroicon-o-cash')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('calculated_volume')
                                        ->label('Розрахований обʼєм')
                                        ->content(fn ($record, $get) => $record ? number_format($record->calculateTotalVolume(), 2) . ' м³' :
                                             (number_format(collect($get('items') ?? [])->sum(fn ($item) => ($item['volume'] ?? 0) * ($item['quantity'] ?? 1)), 2) . ' м³')
                                        )
                                        ->helperText('Автоматично розраховується з предметів'),
                                    Forms\Components\Placeholder::make('calculated_weight')
                                        ->label('Розрахована вага')
                                        ->content(fn ($record, $get) => $record ? number_format($record->calculateTotalWeight(), 2) . ' кг' :
                                             (number_format(collect($get('items') ?? [])->sum(fn ($item) => ($item['weight'] ?? 0) * ($item['quantity'] ?? 1)), 2) . ' кг')
                                        )
                                        ->helperText('Автоматично розраховується з предметів'),
                                    TextInput::make('total_volume')
                                        ->label('Загальний обʼєм, м³')
                                        ->numeric()
                                        ->helperText('Можна вказати вручну або залишити для автоматичного розрахунку')
                                        ->nullable(),
                                    TextInput::make('total_weight')
                                        ->label('Загальна вага, кг')
                                        ->numeric()
                                        ->helperText('Можна вказати вручну або залишити для автоматичного розрахунку')
                                        ->nullable(),
                                    Forms\Components\Placeholder::make('calculated_price')
                                        ->label('Розрахована ціна')
                                        ->content(fn ($record) => $record ? number_format($record->calculateTotalPrice(), 2) . ' NOK' : '—'
                                        )
                                        ->helperText('Автоматично розраховується на основі параметрів'),
                                    TextInput::make('estimated_price')
                                        ->label('Орієнтовна вартість, NOK')
                                        ->numeric()
                                        ->helperText('Можна вказати вручну або залишити для автоматичного розрахунку')
                                        ->nullable(),
                                    TextInput::make('final_price')
                                        ->label('Фінальна вартість, NOK')
                                        ->numeric()
                                        ->helperText('Вказується після виконання замовлення')
                                        ->nullable(),
                                    TextInput::make('estimated_duration_minutes')
                                        ->label('Орієнтовна тривалість, хв')
                                        ->numeric()
                                        ->minValue(30)
                                        ->step(30)
                                        ->helperText('Мінімум 30 хвилин')
                                        ->nullable(),
                                ]),
                            Select::make('executor_team_id')
                                ->relationship('executorTeam', 'name')
                                ->label('Призначена бригада')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            Textarea::make('executor_notes')
                                ->label('Нотатки для бригади')
                                ->columnSpan('full'),
                        ]),
                ])->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Клієнт')
                    ->searchable()
                    ->description(fn ($record) => $record->user->phone ?? null),
                TextColumn::make('from_address_string')
                    ->label('Звідки')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->from_address_string)
                    ->toggleable(),
                TextColumn::make('to_address_string')
                    ->label('Куди')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->to_address_string)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->color(fn ($state = null): string => match ((string) $state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn ($state = null) => self::statusOptions()[(string) $state] ?? (string) $state),
                TextColumn::make('package_type')
                    ->label('Пакет')
                    ->color(fn ($state = null): string => match ((string) $state) {
                        'standard' => 'primary',
                        'premium' => 'success',
                        'economy' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state = null) => self::packageOptions()[(string) $state] ?? (string) $state),
                TextColumn::make('scheduled_at')
                    ->label('Заплановано')
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
                    ->sortable(),
                TextColumn::make('estimated_price')
                    ->label('Орієнтована ціна')
                    ->money('nok')
                    ->sortable()
                    ->description(fn ($record) => $record->final_price ? 'Фінальна: ' . number_format($record->final_price, 2) . ' NOK' : null),
                TextColumn::make('total_volume')
                    ->label('Обʼєм')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' м³' : '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_weight')
                    ->label('Вага')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' кг' : '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('distance')
                    ->label('Відстань')
                    ->formatStateUsing(fn ($record) => $record->distance ? number_format($record->distance, 1) . ' км' : '—')
                    ->sortable(false)
                    ->toggleable(),
                TextColumn::make('executorTeam.name')
                    ->label('Бригада')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Створено')
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
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options(self::statusOptions()),
                SelectFilter::make('package_type')
                    ->label('Пакет послуг')
                    ->multiple()
                    ->options(self::packageOptions()),
                Tables\Filters\Filter::make('without_team')
                    ->label('Без призначеної бригади')
                    ->query(fn ($query) => $query->whereNull('executor_team_id')),
                Tables\Filters\Filter::make('with_service')
                    ->label('З послугою')
                    ->form([
                        Forms\Components\Select::make('service')
                            ->label('Послуга')
                            ->options(self::serviceOptions())
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['service'] ?? null,
                            fn (Builder $query, $service): Builder => $query->whereJsonContains('services->' . $service, true)
                        );
                    }),
                Tables\Filters\Filter::make('scheduled_at')
                    ->label('Запланировано')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('scheduled_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_at', '<=', $date),
                            );
                    }),
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
                                $data['created_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('recalculate_price')
                    ->label('Пересчитать цену')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->action(function (MovingOrder $record) {
                        $record->recalculatePrice();
                        \Filament\Notifications\Notification::make()
                            ->title('Цена пересчитана')
                            ->body('Новая цена: ' . number_format($record->estimated_price, 2) . ' NOK')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('recalculate_totals')
                    ->label('Пересчитать объем/вес')
                    ->icon('heroicon-o-refresh')
                    ->color('info')
                    ->action(function (MovingOrder $record) {
                        $record->recalculateTotals();
                        \Filament\Notifications\Notification::make()
                            ->title('Объем и вес пересчитаны')
                            ->body('Объем: ' . number_format($record->total_volume, 2) . ' м³, Вес: ' . number_format($record->total_weight, 2) . ' кг')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'moving_orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Клиент', 'Статус', 'Пакет', 'Запланировано', 'Создано', 'Ориентировочная цена']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->user->name ?? '—',
                                    $record->status,
                                    $record->package_type,
                                    $record->scheduled_at ? $record->scheduled_at->format('Y-m-d H:i:s') : '—',
                                    $record->created_at->format('Y-m-d H:i:s'),
                                    $record->estimated_price ? number_format($record->estimated_price, 2, ',', ' ') . ' kr' : '—',
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('change_status')
                    ->label('Изменить статус')
                    ->icon('heroicon-o-refresh')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Новый статус')
                            ->options(self::statusOptions())
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $record->update(['status' => $data['status']]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Статус изменен')
                            ->body('Обновлено заказов: ' . $records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovingOrders::route('/'),
            'create' => Pages\CreateMovingOrder::route('/create'),
            'view' => Pages\ViewMovingOrder::route('/{record}'),
            'edit' => Pages\EditMovingOrder::route('/{record}/edit'),
        ];
    }

    protected static function addressSchema(): array
    {
        return [
            TextInput::make('street')
                ->label('Адреса')
                ->required(),
            Select::make('building_type')
                ->label('Тип будівлі')
                ->options([
                    'apartment' => 'Багатоквартирний будинок',
                    'house' => 'Приватний будинок',
                    'office' => 'Офіс',
                    'warehouse' => 'Склад',
                ])
                ->searchable(),
            TextInput::make('floor')
                ->label('Поверх')
                ->numeric(),
            Toggle::make('has_elevator')
                ->label('Є ліфт'),
            TextInput::make('lat')
                ->label('Широта')
                ->numeric()
                ->nullable(),
            TextInput::make('lng')
                ->label('Довгота')
                ->numeric()
                ->nullable(),
        ];
    }

    protected static function packageOptions(): array
    {
        return [
            'economy' => 'Економ',
            'standard' => 'Стандарт',
            'premium' => 'Преміум',
        ];
    }

    protected static function statusOptions(): array
    {
        return [
            'pending' => 'Очікує підтвердження',
            'confirmed' => 'Підтверджено',
            'in_progress' => 'В роботі',
            'completed' => 'Завершено',
            'cancelled' => 'Скасовано',
        ];
    }

    protected static function serviceOptions(): array
    {
        return [
            'assembly' => 'Збірка меблів',
            'disassembly' => 'Розбирання меблів',
            'packaging' => 'Пакування',
            'wrapping' => 'Обгортання плівкою',
            'takelage' => 'Такелажні роботи',
            'electronics' => 'Підключення електроніки',
            'disposal' => 'Утилізація старих речей',
            'cleaning' => 'Прибирання після переїзду',
        ];
    }
}

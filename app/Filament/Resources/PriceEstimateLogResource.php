<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasUltraProMaxFeatures;
use App\Filament\Resources\PriceEstimateLogResource\Pages;
use App\Models\PriceEstimateLog;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PriceEstimateLogResource extends Resource
{
    use HasUltraProMaxFeatures;

    protected static ?string $model = PriceEstimateLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $modelLabel = 'Лог расчёта цен';

    protected static ?string $pluralModelLabel = 'Логи расчёта цен';

    protected static ?string $navigationGroup = 'Аналитика';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        Placeholder::make('uuid')
                            ->label('UUID')
                            ->content(fn (?PriceEstimateLog $record) => $record?->uuid ?? '—'),
                        Placeholder::make('service_type')
                            ->label('Тип услуги')
                            ->content(fn (?PriceEstimateLog $record) => $record?->service_type ?? '—'),
                        Placeholder::make('zone')
                            ->label('Зона')
                            ->content(fn (?PriceEstimateLog $record) => $record?->zone ?? '—'),
                        Placeholder::make('currency')
                            ->label('Валюта')
                            ->content(fn (?PriceEstimateLog $record) => $record?->currency ?? 'NOK'),
                        Placeholder::make('user')
                            ->label('Пользователь')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record?->user) {
                                    return 'Гость';
                                }

                                return "{$record->user->name} ({$record->user->email})";
                            }),
                    ])
                    ->columns(2),

                Section::make('Расчёт цены')
                    ->schema([
                        Placeholder::make('subtotal')
                            ->label('Промежуточная сумма')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record) {
                                    return '—';
                                }

                                return number_format($record->subtotal ?? 0, 2, ',', ' ').' '.($record->currency ?? 'NOK');
                            }),
                        Placeholder::make('total')
                            ->label('Итоговая сумма')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record) {
                                    return '—';
                                }

                                return number_format($record->total ?? 0, 2, ',', ' ').' '.($record->currency ?? 'NOK');
                            }),
                        Placeholder::make('duration')
                            ->label('Время выполнения')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record || ! $record->duration_ms) {
                                    return '—';
                                }
                                $ms = $record->duration_ms;
                                if ($ms < 1000) {
                                    return number_format($ms).' мс';
                                }

                                return number_format($ms / 1000, 2).' сек';
                            }),
                        Placeholder::make('performance')
                            ->label('Производительность')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record || ! $record->duration_ms) {
                                    return '—';
                                }
                                $ms = $record->duration_ms;
                                if ($ms < 100) {
                                    return 'Отлично';
                                }
                                if ($ms < 500) {
                                    return 'Хорошо';
                                }
                                if ($ms < 1000) {
                                    return 'Нормально';
                                }

                                return 'Медленно';
                            }),
                    ])
                    ->columns(2),

                Section::make('Детали запроса')
                    ->schema([
                        Forms\Components\KeyValue::make('payload')
                            ->label('Входные данные')
                            ->disableEditingKeys()
                            ->disableEditingValues()
                            ->columnSpan('full'),
                        Forms\Components\KeyValue::make('result')
                            ->label('Результат расчёта')
                            ->disableEditingKeys()
                            ->disableEditingValues()
                            ->columnSpan('full'),
                    ])
                    ->collapsed(),

                Section::make('Метаданные')
                    ->schema([
                        Placeholder::make('request_hash')
                            ->label('Хеш запроса')
                            ->content(fn (?PriceEstimateLog $record) => $record?->request_hash ?? '—'),
                        Placeholder::make('ip_address')
                            ->label('IP адрес')
                            ->content(fn (?PriceEstimateLog $record) => $record?->ip_address ?? '—'),
                        Placeholder::make('created_at')
                            ->label('Создан')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record?->created_at) {
                                    return '—';
                                }

                                return $record->created_at->format('d.m.Y H:i:s');
                            }),
                        Placeholder::make('updated_at')
                            ->label('Обновлён')
                            ->content(function (?PriceEstimateLog $record) {
                                if (! $record?->updated_at) {
                                    return '—';
                                }

                                return $record->updated_at->format('d.m.Y H:i:s');
                            }),
                    ])
                    ->columns(2)
                    ->collapsed(),
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
                BadgeColumn::make('service_type')
                    ->label('Тип услуги')
                    ->searchable()
                    ->sortable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('zone')
                    ->label('Зона')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->description(fn ($record) => $record->user?->email ?? 'Гость')
                    ->searchable()
                    ->sortable()
                    ->default('Гость'),
                BadgeColumn::make('currency')
                    ->label('Валюта')
                    ->sortable()
                    ->colors([
                        'success' => 'NOK',
                        'info' => 'EUR',
                        'warning' => 'USD',
                    ]),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Промежуточная сумма')
                    ->getStateUsing(function ($record) {
                        $state = $record->subtotal ?? 0;
                        $currency = $record->currency ?? 'NOK';

                        return number_format($state, 2, ',', ' ').' '.$currency;
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Итоговая сумма')
                    ->getStateUsing(function ($record) {
                        $state = $record->total ?? 0;
                        $currency = $record->currency ?? 'NOK';

                        return number_format($state, 2, ',', ' ').' '.$currency;
                    })
                    ->sortable()
                    ->color(function ($record) {
                        return ($record->total ?? 0) > 1000 ? 'success' : null;
                    }),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Время выполнения')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $state = $record->duration_ms;
                        if (! $state) {
                            return '—';
                        }

                        return $state < 1000 ? number_format($state).' мс' : number_format($state / 1000, 2).' сек';
                    })
                    ->color(function ($record) {
                        $state = $record->duration_ms;
                        if (! $state) {
                            return null;
                        }
                        if ($state < 100) {
                            return 'success';
                        }
                        if ($state < 500) {
                            return 'info';
                        }
                        if ($state < 1000) {
                            return 'warning';
                        }

                        return 'danger';
                    }),
                IconColumn::make('user_id')
                    ->label('Авторизован')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->label('Тип услуги')
                    ->options(function () {
                        return PriceEstimateLog::query()
                            ->select('service_type')
                            ->distinct()
                            ->orderBy('service_type')
                            ->pluck('service_type', 'service_type')
                            ->toArray();
                    })
                    ->multiple(),
                SelectFilter::make('zone')
                    ->label('Зона')
                    ->options(function () {
                        return PriceEstimateLog::query()
                            ->whereNotNull('zone')
                            ->distinct()
                            ->orderBy('zone')
                            ->pluck('zone', 'zone')
                            ->toArray();
                    })
                    ->multiple(),
                SelectFilter::make('currency')
                    ->label('Валюта')
                    ->options([
                        'NOK' => 'NOK',
                        'EUR' => 'EUR',
                        'USD' => 'USD',
                    ])
                    ->multiple(),
                SelectFilter::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->searchable(),
                Filter::make('has_user')
                    ->label('Только авторизованные')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id')),
                Filter::make('guest_only')
                    ->label('Только гости')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('user_id')),
                Filter::make('total_range')
                    ->label('Диапазон суммы')
                    ->form([
                        Forms\Components\TextInput::make('total_from')
                            ->label('От')
                            ->numeric(),
                        Forms\Components\TextInput::make('total_until')
                            ->label('До')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['total_from'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('total', '>=', $value),
                            )
                            ->when(
                                $data['total_until'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('total', '<=', $value),
                            );
                    }),
                Filter::make('duration_range')
                    ->label('Время выполнения')
                    ->form([
                        Forms\Components\TextInput::make('duration_from')
                            ->label('От (мс)')
                            ->numeric(),
                        Forms\Components\TextInput::make('duration_until')
                            ->label('До (мс)')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['duration_from'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('duration_ms', '>=', $value),
                            )
                            ->when(
                                $data['duration_until'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('duration_ms', '<=', $value),
                            );
                    }),
                Filter::make('created_range')
                    ->label('Дата создания')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('С'),
                        Forms\Components\DatePicker::make('created_until')->label('По'),
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
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Просмотр'),
                Tables\Actions\Action::make('duplicate')
                    ->label('Дублировать')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(fn () => false), // Логи не дублируются
            ])
            ->bulkActions([
                ...static::getEnhancedBulkActions(),
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Удалить выбранные'),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceEstimateLogs::route('/'),
            'view' => Pages\ViewPriceEstimateLog::route('/{record}'),
        ];
    }
}

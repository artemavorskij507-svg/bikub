<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricingRuleResource\Pages;
use App\Models\PricingRule;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 120;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Базовые параметры')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Слаг')
                            ->maxLength(255)
                            ->helperText('Оставьте пустым, чтобы создать автоматически.'),
                        Forms\Components\Select::make('service_type_id')
                            ->label('Тип услуги')
                            ->relationship('serviceType', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->label('Тип правила')
                            ->required()
                            ->options([
                                'base_fee' => 'Базовая цена',
                                'flat' => 'Фиксированная сумма',
                                'distance' => 'За километр',
                                'weight_surcharge' => 'Доплата за вес',
                                'time_multiplier' => 'Множитель по времени',
                                'percentage' => 'Процент от суммы',
                                'service_specific' => 'Спец. правило',
                                'demand_multiplier' => 'Множитель спроса',
                            ]),
                        Forms\Components\TextInput::make('value')
                            ->label('Значение')
                            ->numeric()
                            ->step('0.01'),
                        Forms\Components\TextInput::make('currency')
                            ->label('Валюта')
                            ->maxLength(3)
                            ->default('NOK'),
                        Forms\Components\TextInput::make('unit')
                            ->label('Единица')
                            ->maxLength(50)
                            ->helperText('Например km, kg, %, fee'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                    ])->columns(2),
                Forms\Components\Section::make('Активность и приоритет')
                    ->schema([
                        Forms\Components\TextInput::make('priority')
                            ->label('Приоритет')
                            ->numeric()
                            ->default(100),
                        Forms\Components\Toggle::make('active')
                            ->label('Включено')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Действует с'),
                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Действует до'),
                    ])->columns(2),
                Forms\Components\Section::make('Область и условия')
                    ->schema([
                        Forms\Components\KeyValue::make('applies_to')
                            ->label('Применяется к (service_types[], categories[], zones[])')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение'),
                        Forms\Components\KeyValue::make('conditions')
                            ->label('Условия (min_weight, hours[18,22], max_distance_km...)'),
                        Forms\Components\KeyValue::make('meta')
                            ->label('Доп. параметры (min_weight_kg, per_kg и др.)'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'primary' => ['base_fee', 'flat'],
                        'success' => ['percentage', 'time_multiplier'],
                        'warning' => ['distance', 'weight_surcharge'],
                        'danger' => ['demand_multiplier'],
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Услуга')
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Значение')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Период действия')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record) {
                            return '—';
                        }

                        $from = $record->valid_from
                            ? $record->valid_from->format('d.m.Y H:i')
                            : '—';

                        $to = $record->valid_until
                            ? $record->valid_until->format('d.m.Y H:i')
                            : '—';

                        return $from.' → '.$to;
                    })
                    ->tooltip('Интервал действия правила'),
                Tables\Columns\IconColumn::make('active')
                    ->label('Включено')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->label('Обновлено'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->multiple()
                    ->options([
                        'base_fee' => 'Base fee',
                        'flat' => 'Flat',
                        'distance' => 'Distance',
                        'weight_surcharge' => 'Weight',
                        'time_multiplier' => 'Time multiplier',
                        'percentage' => 'Percentage',
                        'service_specific' => 'Service specific',
                        'demand_multiplier' => 'Demand',
                    ]),
                Tables\Filters\SelectFilter::make('service_type_id')
                    ->relationship('serviceType', 'name')
                    ->label('Услуга')
                    ->searchable()
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Включено')
                    ->trueLabel('Да')
                    ->falseLabel('Нет'),
                Tables\Filters\Filter::make('valid_now')
                    ->label('Действует сейчас')
                    ->query(fn (Builder $query): Builder => $query->where('active', true)
                        ->where(function ($q) {
                            $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                        })
                    ),
                Tables\Filters\Filter::make('priority_range')
                    ->label('Приоритет')
                    ->form([
                        Forms\Components\TextInput::make('priority_from')
                            ->label('От')
                            ->numeric(),
                        Forms\Components\TextInput::make('priority_to')
                            ->label('До')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['priority_from'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('priority', '>=', $value),
                            )
                            ->when(
                                $data['priority_to'] ?? null,
                                fn (Builder $query, $value): Builder => $query->where('priority', '<=', $value),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (PricingRule $record) => $record->active ? 'Отключить' : 'Включить')
                    ->icon(fn (PricingRule $record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (PricingRule $record) => $record->active ? 'warning' : 'success')
                    ->action(function (PricingRule $record) {
                        $record->update(['active' => ! $record->active]);
                        \Filament\Notifications\Notification::make()
                            ->title($record->active ? 'Правило включено' : 'Правило отключено')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'pricing_rules_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Название', 'Тип', 'Услуга', 'Значение', 'Валюта', 'Единица', 'Приоритет', 'Включено', 'Действует с', 'Действует до']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->name,
                                    $record->type,
                                    $record->serviceType->name ?? '—',
                                    $record->value ?? '—',
                                    $record->currency ?? 'NOK',
                                    $record->unit ?? '—',
                                    $record->priority,
                                    $record->active ? 'Да' : 'Нет',
                                    $record->valid_from ? $record->valid_from->format('Y-m-d H:i:s') : '—',
                                    $record->valid_until ? $record->valid_until->format('Y-m-d H:i:s') : '—',
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Включить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['active' => true]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Правила включены')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Отключить')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['active' => false]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Правила отключены')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('priority');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }
}

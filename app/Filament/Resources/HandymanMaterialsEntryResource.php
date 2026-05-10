<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HandymanMaterialsEntryResource\Pages;
use App\Models\HandymanMaterialsEntry;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class HandymanMaterialsEntryResource extends Resource
{
    protected static ?string $model = HandymanMaterialsEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 403;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Связи')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Заказ (опционально)')
                            ->relationship('order', 'order_number')
                            ->preload()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    return;
                                }
                                $order = Order::with('repairProject')->find($state);
                                if (! $order) {
                                    return;
                                }
                                // Подставляем проект ремонта, если он есть.
                                if ($order->repairProject?->id) {
                                    $set('repair_project_id', $order->repairProject->id);
                                }
                            })
                            ->helperText('Опционально свяжите материалы с конкретным заказом.'),
                        Forms\Components\Select::make('repair_project_id')
                            ->label('Проект ремонта')
                            ->relationship('repairProject', 'title')
                            ->searchable()
                            ->nullable()->helperText('Если материалы относятся к конкретному проекту ремонта.'),
                        Forms\Components\Select::make('executor_profile_id')
                            ->label('Исполнитель')
                            ->relationship('executorProfile', 'id')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->user?->name ?? "Профиль #{$record->id}"
                            )
                            ->preload()
                            ->searchable()
                            ->required()
                            ->helperText('Кто закупил или использует эти материалы.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Материалы')
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label('Материал')
                            ->required()
                            ->placeholder('Например: Гипсокартон 12 мм, Краска белая, Шурупы 4х40'),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Количество')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->reactive()
                            ->placeholder('Например, 3')
                            ->helperText('Можно указать дробное количество (1.5 листа, 2.3 л и т.п.).'),
                        Forms\Components\TextInput::make('unit')
                            ->label('Единица измерения')
                            ->maxLength(25)
                            ->placeholder('шт, м², м, л и т.п.')->nullable(),
                        Forms\Components\TextInput::make('unit_price_minor')
                            ->label('Цена за единицу (в коп.)')
                            ->numeric()
                            ->minValue(0)
                            ->reactive()
                            ->helperText('Цена хранится в "копейках" (minor units), например 100 NOK = 10000.')
                            ->nullable()
                            ->placeholder('Например, 19900 (за 199,00 NOK)')
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $qty = (float) ($get('quantity') ?? 0);
                                $unit = (float) ($state ?? 0);
                                if ($qty > 0 && $unit > 0) {
                                    $set('total_price_minor', (int) round($qty * $unit));
                                }
                            }),
                        Forms\Components\TextInput::make('total_price_minor')
                            ->label('Общая сумма (в коп.)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Можно оставить пустым — сумма автоматически посчитается как Кол-во × Цена за единицу, но при необходимости её можно скорректировать вручную.')
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Если пользователь вручную ввёл сумму — не пересчитываем.
                                if ($state !== null && $state !== '') {
                                    return;
                                }
                                $qty = (float) ($get('quantity') ?? 0);
                                $unit = (float) ($get('unit_price_minor') ?? 0);
                                if ($qty > 0 && $unit > 0) {
                                    $set('total_price_minor', (int) round($qty * $unit));
                                }
                            }),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\DateTimePicker::make('purchased_at')
                            ->label('Дата покупки')
                            ->default(fn () => now())
                            ->nullable(),
                        Forms\Components\TextInput::make('receipt_url')
                            ->label('Ссылка на чек')
                            ->url()
                            ->placeholder('https://...')
                            ->nullable(),
                        Forms\Components\KeyValue::make('meta')
                            ->label('Доп. данные')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->nullable()
                            ->helperText('Служебная информация: артикул, поставщик, склад и т.п.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Материал')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->description(fn (HandymanMaterialsEntry $record) => $record->order?->user?->name ?? $record->order?->user?->email)
                    ->url(fn (HandymanMaterialsEntry $record) => $record->order ? route('filament.resources.orders.view', $record->order) : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('repairProject.title')
                    ->label('Проект')
                    ->sortable()
                    ->toggleable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('executorProfile.user.name')
                    ->label('Исполнитель')
                    ->sortable()
                    ->searchable()->default('—'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Кол-во')
                    ->formatStateUsing(fn ($state) => $state !== null ? rtrim(rtrim(number_format((float) $state, 2, ',', ' '), '0'), ',') : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Ед.')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('unit_price_minor')
                    ->label('Цена за ед., NOK')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, ',', ' ').' NOK' : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_price_minor')
                    ->label('Сумма, NOK')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, ',', ' ').' NOK' : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchased_at')
                    ->label('Покупка')
                    ->dateTime('d.m.Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('executor_profile_id')
                    ->label('Исполнитель')
                    ->relationship('executorProfile', 'id')
                    ->options(fn () => \App\Models\Moving\ExecutorProfile::query()
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn ($profile) => [$profile->id => $profile->user?->name ?? "Профиль #{$profile->id}"])
                        ->toArray()
                    ),
                Tables\Filters\Filter::make('purchased_at')
                    ->label('Дата покупки')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('until')
                            ->label('До'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn ($q, $date) => $q->whereDate('purchased_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn ($q, $date) => $q->whereDate('purchased_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHandymanMaterialsEntries::route('/'),
            'create' => Pages\CreateHandymanMaterialsEntry::route('/create'),
            'edit' => Pages\EditHandymanMaterialsEntry::route('/{record}/edit'),
        ];
    }
}

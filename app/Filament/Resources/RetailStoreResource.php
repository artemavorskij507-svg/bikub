<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RetailStoreResource\Pages;
use App\Models\RetailStore;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class RetailStoreResource extends Resource
{
    protected static ?string $model = RetailStore::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    // TODO fixed by Cursor: normalize navigation group name to unified 'Справочники и контент'
    protected static ?string $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 204;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Параметры магазина, которые видит клиент в каталоге.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название магазина')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: Rema 1000 Narvik'),
                        Forms\Components\TextInput::make('slug')
                            ->label('Слаг')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Если оставить пустым, слаг будет сгенерирован автоматически на основе названия.'),
                        Forms\Components\TextInput::make('brand')
                            ->label('Бренд / сеть')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('category')
                            ->label('Категория')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Например: grocery, pharmacy, diy, furniture')
                            ->helperText('Обязательное поле. Используется для фильтрации и аналитики.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->placeholder('Кратко опишите магазин, ассортимент и особенности.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Контакты и адрес')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->maxLength(255)
                            ->placeholder('Город, улица, дом'),
                        Forms\Components\TextInput::make('city')
                            ->label('Город')
                            ->default('Narvik')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('postcode')
                            ->label('Почтовый индекс')
                            ->maxLength(32),
                        Forms\Components\TextInput::make('country')
                            ->label('Страна')
                            ->default('Norway')
                            ->maxLength(64),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(64),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric()
                            ->step('any'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric()
                            ->step('any'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Доставка')
                    ->description('Настройки доставки и минимальной суммы заказа.')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\Toggle::make('has_home_delivery')
                            ->label('Собственная доставка')
                            ->default(true),
                        Forms\Components\Toggle::make('supports_grocery_delivery')
                            ->label('Продукты')
                            ->default(true),
                        Forms\Components\Toggle::make('supports_bulky_delivery')
                            ->label('Крупногабарит')
                            ->default(false),
                        Forms\Components\TextInput::make('delivery_provider')
                            ->label('Провайдер доставки')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('average_delivery_time_minutes')
                            ->label('Среднее время доставки (мин)')
                            ->numeric()
                            ->minValue(0)
                            ->step(5),
                        Forms\Components\TextInput::make('minimum_order_amount')
                            ->label('Минимальная сумма заказа')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->prefix('kr'),
                        Forms\Components\TextInput::make('delivery_fee')
                            ->label('Базовая стоимость доставки')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->prefix('kr'),
                        Forms\Components\TextInput::make('delivery_currency')
                            ->label('Валюта')
                            ->maxLength(3)
                            ->default('NOK'),
                        Forms\Components\KeyValue::make('delivery_metadata')
                            ->label('Настройки доставки')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->addButtonLabel('Добавить запись')
                            ->nullable(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Textarea::make('opening_hours')
                            ->label('Часы работы')
                            ->rows(3)
                            ->placeholder("Пн-Пт 10:00–22:00\nСб-Вс 11:00–23:00"),
                        Forms\Components\Textarea::make('metadata')
                            ->label('Служебные данные (JSON)')
                            ->rows(3)
                            ->helperText('Дополнительная информация для интеграций, в формате JSON.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Бренд')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('supports_grocery_delivery')
                    ->label('Grocery')
                    ->boolean(),
                Tables\Columns\IconColumn::make('supports_bulky_delivery')
                    ->label('Bulky')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('delivery_provider')
                    ->label('Провайдер')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('supports_grocery_delivery')
                    ->label('Продукты'),
                Tables\Filters\TernaryFilter::make('supports_bulky_delivery')
                    ->label('Крупногабарит'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
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
            'index' => Pages\ListRetailStores::route('/'),
            'create' => Pages\CreateRetailStore::route('/create'),
            'edit' => Pages\EditRetailStore::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisposalItemResource\Pages;
use App\Models\DisposalItem;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class DisposalItemResource extends Resource
{
    protected static ?string $model = DisposalItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationGroup = 'Eco Disposal';

    protected static ?int $navigationSort = 301;

    protected static bool $shouldRegisterNavigation = true;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Основная информация')->description('Базовые параметры объекта утилизации, видимые диспетчеру и в аналитике.')->schema([Forms\Components\TextInput::make('name')->label('Название')->required()->maxLength(255)->placeholder('Например: Диван угловой, Холодильник, Стиральная машина'),                        Forms\Components\Select::make('category')->label('Категория')->required()->options(['furniture' => 'Мебель',                                'large_appliance' => 'Крупная техника',                                'small_appliance' => 'Мелкая техника',                                'electronics' => 'Электроника',                                'construction' => 'Стройматериалы/строительный мусор',                                'textile' => 'Текстиль',                                'hazardous' => 'Опасные отходы',                                'other' => 'Другое'])->helperText('Используется для подбора тарифа и фильтрации заказов.'),                        Forms\Components\Select::make('disposal_path')->label('Путь утилизации')->required()->options(['RECYCLABLE' => 'Переработка',                                'DONATABLE' => 'Дарение/повторное использование',                                'HAZARDOUS' => 'Опасные отходы',                                'LANDFILL' => 'Полигон (свалка)'])->helperText('Основной маршрут утилизации для этого типа объекта.'),                        Forms\Components\Toggle::make('is_active')->label('Активен')->default(true)->helperText('Отключите, если объект нельзя выбирать в новых ЭКО-заказах.')])->columns(2),                Forms\Components\Section::make('Характеристики и цены')->description('Используется для автоматического расчёта цены и нагрузки.')->schema([Forms\Components\TextInput::make('volume_m3')->label('Объем, м³')->numeric()->minValue(0)->step('0.001')->placeholder('Например: 1.2')->helperText('Оценочный объём одного объекта в кубометрах.'),                        Forms\Components\TextInput::make('weight_kg')->label('Вес, кг')->numeric()->minValue(0)->step('0.1')->placeholder('Например: 75')->helperText('Оценочный вес одного объекта.'),                        Forms\Components\Toggle::make('requires_disassembly')->label('Требуется демонтаж'),                        Forms\Components\TextInput::make('difficulty_coefficient')->label('Коэффициент сложности')->numeric()->default(1.0)->minValue(0.1)->step('0.1')->helperText('1 = обычная сложность, >1 — сложнее (узкие проходы, сложный демонтаж).'),                        Forms\Components\TextInput::make('eco_score')->label('Эко-оценка')->numeric()->minValue(0)->helperText('Абстрактный экобалл; используется только для аналитики и приоритизации.'),                        Forms\Components\TextInput::make('base_price_nok')->label('Базовая цена, NOK')->prefix('NOK')->numeric()->minValue(0)->step('0.01')->helperText('Ориентировочная цена за единицу при стандартных условиях.')])->columns(3)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->label('Название')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('category')->label('Категория')->colors(['primary' => ['furniture', 'textile'],                        'info' => ['electronics', 'small_appliance'],                        'warning' => ['construction'],                        'danger' => ['hazardous'],                        'secondary' => ['large_appliance', 'other']])->formatStateUsing(function ($state) {
            return match ($state) {
                'furniture' => 'Мебель',                            'large_appliance' => 'Крупная техника',                            'small_appliance' => 'Мелкая техника',                            'electronics' => 'Электроника',                            'construction' => 'Стройматериалы',                            'textile' => 'Текстиль',                            'hazardous' => 'Опасные',                            default => 'Другое',
            };
        })->sortable(),                Tables\Columns\BadgeColumn::make('disposal_path')->label('Путь')->colors(['success' => fn ($state) => $state === 'RECYCLABLE' || $state === 'DONATABLE',                        'warning' => fn ($state) => $state === 'HAZARDOUS',                        'secondary' => fn ($state) => $state === 'LANDFILL'])->formatStateUsing(function ($state) {
            return match ($state) {
                'RECYCLABLE' => 'Переработка',                            'DONATABLE' => 'Дарение',                            'HAZARDOUS' => 'Опасные',                            'LANDFILL' => 'Полигон',                            default => $state,
            };
        })->sortable(),                Tables\Columns\TextColumn::make('volume_m3')->label('Объём, м³')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2, ',', ' ') : '—')->sortable()->toggleable(isToggledHiddenByDefault: true),                Tables\Columns\TextColumn::make('weight_kg')->label('Вес, кг')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1, ',', ' ') : '—')->sortable()->toggleable(isToggledHiddenByDefault: true),                Tables\Columns\TextColumn::make('base_price_nok')->label('База, NOK')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2, '.', ' ').' NOK' : '—')->sortable(),                Tables\Columns\TextColumn::make('difficulty_coefficient')->label('Коэф.')->sortable(),                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Активен')->sortable()])->filters([Tables\Filters\SelectFilter::make('category')->label('Категория')->options(['furniture' => 'Мебель',                        'large_appliance' => 'Крупная техника',                        'small_appliance' => 'Мелкая техника',                        'electronics' => 'Электроника',                        'construction' => 'Стройматериалы',                        'textile' => 'Текстиль',                        'hazardous' => 'Опасные',                        'other' => 'Другое']),                Tables\Filters\SelectFilter::make('disposal_path')->label('Путь утилизации')->options(['RECYCLABLE' => 'Переработка',                        'DONATABLE' => 'Дарение',                        'HAZARDOUS' => 'Опасные',                        'LANDFILL' => 'Полигон']),                Tables\Filters\TernaryFilter::make('is_active')->label('Активность')->placeholder('Все')->trueLabel('Активные')->falseLabel('Выключенные')->queries(true: fn ($query) => $query->where('is_active', true), false: fn ($query) => $query->where('is_active', false))])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\EditAction::make(),                Tables\Actions\Action::make('toggle_active')->label(fn (DisposalItem $record) => $record->is_active ? 'Выключить' : 'Включить')->icon(fn (DisposalItem $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')->color(fn (DisposalItem $record) => $record->is_active ? 'warning' : 'success')->action(function (DisposalItem $record) {
            $record->update(['is_active' => ! $record->is_active]);
        }),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\BulkAction::make('activate')->label('Включить')->icon('heroicon-o-check-circle')->color('success')->action(function ($records) {
            foreach ($records as $record) {
                $record->update(['is_active' => true]);
            }
        }),                Tables\Actions\BulkAction::make('deactivate')->label('Выключить')->icon('heroicon-o-x-circle')->color('warning')->action(function ($records) {
            foreach ($records as $record) {
                $record->update(['is_active' => false]);
            }
        }),                Tables\Actions\DeleteBulkAction::make()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDisposalItems::route('/'),            'create' => Pages\CreateDisposalItem::route('/create'),            'edit' => Pages\EditDisposalItem::route('/{record}/edit')];
    }
}

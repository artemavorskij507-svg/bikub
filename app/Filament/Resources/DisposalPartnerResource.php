<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisposalPartnerResource\Pages;
use App\Models\DisposalPartner;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Schema;

class DisposalPartnerResource extends Resource
{
    protected static ?string $model = DisposalPartner::class;

    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    protected static ?string $navigationGroup = 'Eco Disposal';

    protected static ?int $navigationSort = 302;

    protected static bool $shouldRegisterNavigation = true;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Общая информация')->schema([Forms\Components\TextInput::make('name')->label('Название партнёра')->required()->maxLength(255),                        Forms\Components\Select::make('type')->label('Тип партнёра')->options(['RECYCLING_CENTER' => 'Пункт переработки',                                'CHARITY' => 'Благотворительность',                                'HAZARDOUS_PROCESSOR' => 'Обработка опасных отходов',                                'LANDFILL' => 'Полигон'])->required(),                        Forms\Components\Toggle::make('is_active')->label('Активен')->default(true)])->columns(3),                Forms\Components\Section::make('Адрес и координаты')->schema([Forms\Components\TextInput::make('address')->label('Адрес')->maxLength(255),                        Forms\Components\TextInput::make('city')->label('Город')->maxLength(120),                        Forms\Components\TextInput::make('postal_code')->label('Почтовый индекс')->maxLength(20),                        Forms\Components\TextInput::make('latitude')->label('Широта')->numeric()->helperText('Используется для карты и маршрутизации'),                        Forms\Components\TextInput::make('longitude')->label('Долгота')->numeric()->helperText('Используется для карты и маршрутизации')])->columns(3),                Forms\Components\Section::make('Категории и требования')->schema([Forms\Components\Select::make('accepted_categories')->label('Принимаемые категории')->multiple()->options(['furniture' => 'Мебель',                                'large_appliance' => 'Крупная техника',                                'small_appliance' => 'Мелкая техника',                                'electronics' => 'Электроника',                                'construction' => 'Стройматериалы/строительный мусор',                                'textile' => 'Текстиль',                                'hazardous' => 'Опасные отходы',                                'other' => 'Другое'])->helperText('Выберите категории, которые принимает партнёр.'),                        Forms\Components\Textarea::make('requirements')->label('Особые требования')->rows(3)->helperText('Например: упаковка, сортировка, предварительное согласование.'),                        Forms\Components\Textarea::make('licenses')->label('Лицензии / разрешения')->rows(3),                        Forms\Components\KeyValue::make('opening_hours')->label('Часы работы')->helperText('Формат будет уточнен на следующих шагах. Можно оставить пустым или JSON.')])->columns(2),                Forms\Components\Section::make('Контакты')->schema([Forms\Components\TextInput::make('contact_email')->label('Email')->email(),                        Forms\Components\TextInput::make('contact_phone')->label('Телефон')])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->label('Название')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('type')->label('Тип')->formatStateUsing(function ($state) {
            return match ($state) {
                'RECYCLING_CENTER' => 'Пункт переработки',                            'CHARITY' => 'Благотворительность',                            'HAZARDOUS_PROCESSOR' => 'Опасные отходы',                            'LANDFILL' => 'Полигон',                            default => $state,
            };
        })->colors(['success' => fn ($state) => $state === 'RECYCLING_CENTER' || $state === 'CHARITY',                        'warning' => fn ($state) => $state === 'HAZARDOUS_PROCESSOR',                        'secondary' => fn ($state) => $state === 'LANDFILL'])->sortable(),                Tables\Columns\TextColumn::make('city')->label('Город')->sortable()->searchable(),                Tables\Columns\TextColumn::make('postal_code')->label('Индекс')->sortable(),                Tables\Columns\TextColumn::make('accepted_categories')->label('Категории (кол-во)')->formatStateUsing(function ($state) {
            if (is_array($state)) {
                return count($state).' катег.';
            }                        if (is_string($state)) {
                $decoded = json_decode($state, true);

                return is_array($decoded) ? (count($decoded).' катег.') : '—';
            }

return '—';
        }),                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Активен')->sortable()])->filters([Tables\Filters\SelectFilter::make('type')->label('Тип')->options(['RECYCLING_CENTER' => 'Пункт переработки',                        'CHARITY' => 'Благотворительность',                        'HAZARDOUS_PROCESSOR' => 'Опасные отходы',                        'LANDFILL' => 'Полигон']),                Tables\Filters\SelectFilter::make('is_active')->label('Активность')->options([1 => 'Активные',                        0 => 'Неактивные']),                Tables\Filters\SelectFilter::make('city')->label('Город')->options(fn () => ! Schema::hasTable('disposal_partners') ? [] : DisposalPartner::query()->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city', 'city')->toArray())])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDisposalPartners::route('/'),            'create' => Pages\CreateDisposalPartner::route('/create'),            'edit' => Pages\EditDisposalPartner::route('/{record}/edit')];
    }
}

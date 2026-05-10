<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientProfileResource\Pages;
use App\Filament\Resources\ClientProfileResource\RelationManagers;
use App\Models\ClientProfile;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ClientProfileResource extends Resource
{
    protected static ?string $model = ClientProfile::class;

    protected static ?string $navigationGroup = 'Люди';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Клиенты';

    protected static ?string $modelLabel = 'Клиент';

    protected static ?string $pluralModelLabel = 'Клиенты';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Основная информация')->schema([Forms\Components\Select::make('user_id')->label('Пользователь')->relationship('user', 'name')->searchable()->preload()->nullable(),                        Forms\Components\TextInput::make('full_name')->label('Полное имя')->required()->maxLength(255),                        Forms\Components\DatePicker::make('date_of_birth')->label('Дата рождения')->nullable(),                        Forms\Components\TextInput::make('phone')->label('Телефон')->tel()->maxLength(255),                        Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255)])->columns(2),                Forms\Components\Section::make('Адрес')->schema([Forms\Components\TextInput::make('address_line')->label('Адрес')->required()->maxLength(255),                        Forms\Components\TextInput::make('postal_code')->label('Почтовый индекс')->required()->maxLength(255),                        Forms\Components\TextInput::make('city')->label('Город')->required()->maxLength(255)])->columns(3),                Forms\Components\Section::make('Особенности и предпочтения')->schema([Forms\Components\Textarea::make('mobility_notes')->label('Особенности передвижения')->rows(3),                        Forms\Components\Textarea::make('health_notes')->label('Заметки о здоровье')->rows(3)->helperText('Внимание: конфиденциальная информация'),                        Forms\Components\KeyValue::make('communication_preferences')->label('Предпочтения в общении')->keyLabel('Ключ')->valueLabel('Значение'),                        Forms\Components\Toggle::make('is_active')->label('Активен')->default(true)])->collapsible()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('full_name')->label('Имя')->searchable()->sortable(),                Tables\Columns\TextColumn::make('date_of_birth')->label('Дата рождения')->date()->sortable()->toggleable(),                Tables\Columns\TextColumn::make('city')->label('Город')->searchable()->sortable(),                Tables\Columns\TextColumn::make('phone')->label('Телефон')->searchable()->toggleable(),                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),                Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)])->filters([Tables\Filters\TernaryFilter::make('is_active')->label('Активен'),                Tables\Filters\SelectFilter::make('city')->label('Город')->options(function () {
            return ClientProfile::query()->distinct()->pluck('city', 'city')->toArray();
        })])->actions([Tables\Actions\ViewAction::make(),                Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\TrustedContactsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListClientProfiles::route('/'),            'create' => Pages\CreateClientProfile::route('/create'),            'view' => Pages\ViewClientProfile::route('/{record}'),            'edit' => Pages\EditClientProfile::route('/{record}/edit')];
    }
}

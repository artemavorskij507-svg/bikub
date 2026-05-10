<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Контакты';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('full_name')->required(),
            Forms\Components\TextInput::make('role'),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\TextInput::make('phone_e164')->tel(),
            Forms\Components\Toggle::make('is_primary')->label('Основной'),
            Forms\Components\KeyValue::make('notify')->label('Уведомления'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('full_name')->searchable(),
            Tables\Columns\TextColumn::make('role'),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('phone_e164'),
            Tables\Columns\IconColumn::make('is_primary')->boolean()->label('Основной'),
        ])->headerActions([
            Tables\Actions\CreateAction::make(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}

<?php

namespace App\Filament\Resources\ClientProfileResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class TrustedContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'trustedContacts';

    protected static ?string $title = 'Доверенные лица';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\TextInput::make('full_name')
                    ->label('Полное имя')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('relationship')
                    ->label('Отношение')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('дочь, сын, опекун, соцработник'),
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('can_manage_orders')
                    ->label('Может управлять заказами')
                    ->default(true),
                Forms\Components\Toggle::make('can_view_reports')
                    ->label('Может просматривать отчёты')
                    ->default(true),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Основной контакт')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('relationship')
                    ->label('Отношение')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('is_primary')
                    ->label('Основной')
                    ->formatStateUsing(fn ($state) => $state ? 'Да' : 'Нет')
                    ->colors([
                        'success' => fn ($state) => (bool) $state,
                        'gray' => fn ($state) => ! $state,
                    ]),
                Tables\Columns\IconColumn::make('can_manage_orders')
                    ->label('Управление заказами')
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_view_reports')
                    ->label('Просмотр отчётов')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}

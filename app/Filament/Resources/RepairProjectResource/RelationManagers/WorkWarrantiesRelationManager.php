<?php

namespace App\Filament\Resources\RepairProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class WorkWarrantiesRelationManager extends RelationManager
{
    protected static string $relationship = 'workWarranties';

    protected static ?string $title = 'Гарантии на работы';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255),                Forms\Components\Textarea::make('description')->label('Описание')->rows(3)->nullable(),                Forms\Components\DateTimePicker::make('starts_at')->label('Начало действия')->nullable(),                Forms\Components\DateTimePicker::make('ends_at')->label('Окончание действия')->nullable(),                Forms\Components\Select::make('status')->label('Статус')->options(['active' => 'Активна',                        'expired' => 'Истекла',                        'cancelled' => 'Отменена'])->required()->default('active'),                Forms\Components\TextInput::make('terms_url')->label('Ссылка на условия')->url()->nullable()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['success' => 'active',                        'gray' => 'expired',                        'danger' => 'cancelled'])->sortable(),                Tables\Columns\TextColumn::make('starts_at')->label('Начало')->dateTime()->sortable(),                Tables\Columns\TextColumn::make('ends_at')->label('Окончание')->dateTime()->sortable()])->filters([Tables\Filters\SelectFilter::make('status')->label('Статус')->options(['active' => 'Активна',                        'expired' => 'Истекла',                        'cancelled' => 'Отменена'])])->headerActions([Tables\Actions\CreateAction::make()])->actions([Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

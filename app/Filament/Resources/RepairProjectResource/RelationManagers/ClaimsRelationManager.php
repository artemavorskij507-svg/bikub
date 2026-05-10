<?php

namespace App\Filament\Resources\RepairProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;

class ClaimsRelationManager extends RelationManager
{
    protected static string $relationship = 'claims';

    protected static ?string $title = 'Претензии';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Select::make('user_id')->label('Клиент')->relationship('user', 'name')->searchable()->required(),                Forms\Components\Select::make('type')->label('Тип')->options(['quality' => 'Качество',                        'damage' => 'Повреждение',                        'delay' => 'Задержка',                        'billing' => 'Оплата',                        'other' => 'Другое'])->required(),                Forms\Components\Select::make('status')->label('Статус')->options(['open' => 'Открыта',                        'in_review' => 'На рассмотрении',                        'resolved' => 'Решена',                        'rejected' => 'Отклонена',                        'closed' => 'Закрыта'])->required()->default('open'),                Forms\Components\Select::make('severity')->label('Критичность')->options(['low' => 'Низкая',                        'medium' => 'Средняя',                        'high' => 'Высокая'])->nullable(),                Forms\Components\TextInput::make('title')->label('Заголовок')->required()->maxLength(255),                Forms\Components\Textarea::make('description')->label('Описание')->rows(4)->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('title')->label('Претензия')->searchable()->sortable(),                Tables\Columns\BadgeColumn::make('status')->label('Статус')->colors(['warning' => 'open',                        'info' => 'in_review',                        'success' => 'resolved',                        'danger' => 'rejected',                        'secondary' => 'closed'])->sortable(),                Tables\Columns\BadgeColumn::make('type')->label('Тип')->sortable(),                Tables\Columns\BadgeColumn::make('severity')->label('Критичность')->sortable(),                Tables\Columns\TextColumn::make('opened_at')->label('Открыта')->dateTime()->sortable()])->filters([Tables\Filters\SelectFilter::make('status')->label('Статус')->options(['open' => 'Открыта',                        'in_review' => 'На рассмотрении',                        'resolved' => 'Решена',                        'rejected' => 'Отклонена',                        'closed' => 'Закрыта'])])->headerActions([Tables\Actions\CreateAction::make()])->actions([Tables\Actions\EditAction::make(),                Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
}

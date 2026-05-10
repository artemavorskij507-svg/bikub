<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkWarrantyResource\Pages;
use App\Models\WorkWarranty;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class WorkWarrantyResource extends Resource
{
    protected static ?string $model = WorkWarranty::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 407;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Связь с заказом и проектом')
                    ->description('Укажите, к какому заказу и ремонтному проекту относится эта гарантия.')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Заказ')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Необязательно. Если гарантия привязана к конкретному заказу, выберите его здесь.'),
                        Forms\Components\Select::make('repair_project_id')
                            ->label('Проект')
                            ->relationship('repairProject', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Необязательно. Можно связать гарантию с ремонтным проектом.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Основная информация о гарантии')
                    ->description('Название, описание и статус гарантии для клиента.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->placeholder('Гарантия на работы по ремонту ванной комнаты')
                            ->helperText('Краткое человеко-понятное название гарантии.'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->nullable()
                            ->placeholder('Опишите, какие работы покрываются гарантией и при каких условиях.'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активна',
                                'expired' => 'Истекла',
                                'revoked' => 'Отозвана',
                            ])
                            ->default('active')
                            ->required()
                            ->helperText('Текущий статус гарантии для клиента.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Срок действия и условия')
                    ->description('Укажите период действия гарантии и ссылку на детальные условия.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Начало')
                            ->nullable()
                            ->helperText('Дата, с которой гарантия начинает действовать. Можно оставить пустой.'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Окончание')
                            ->nullable()
                            ->helperText('Дата окончания действия гарантии, если есть.'),
                        Forms\Components\TextInput::make('terms_url')
                            ->label('Ссылка на условия')
                            ->url()
                            ->nullable()
                            ->placeholder('https://example.com/warranty-terms')
                            ->helperText('Необязательно. Ссылка на PDF или страницу с подробными условиями гарантии.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Гарантия')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('repairProject.title')
                    ->label('Проект')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'revoked',
                        'secondary' => 'expired',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
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
            'index' => Pages\ListWorkWarranties::route('/'),
            'create' => Pages\CreateWorkWarranty::route('/create'),
            'edit' => Pages\EditWorkWarranty::route('/{record}/edit'),
        ];
    }
}

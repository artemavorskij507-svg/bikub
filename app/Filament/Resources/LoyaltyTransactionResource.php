<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyTransactionResource\Pages;
use App\Models\LoyaltyTransaction;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class LoyaltyTransactionResource extends Resource
{
    protected static ?string $model = LoyaltyTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Лояльність';

    protected static ?string $navigationLabel = 'Історія операцій';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Операція балів';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Операції балів';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'email')
                            ->required()
                            ->disabled()
                            ->searchable()
                            ->preload()
                            ->label('Користувач'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'earn' => 'Накопичено балів',
                                'redeem' => 'Витрачено балів',
                                'manual_add' => 'Додано вручну',
                                'manual_remove' => 'Видалено вручну',
                                'expire' => 'Закінчилися',
                                'admin_adjustment' => 'Коригування адміністратором',
                            ])
                            ->disabled()
                            ->label('Тип операції'),

                        TextInput::make('points_amount')
                            ->numeric()
                            ->disabled()
                            ->label('Кількість балів'),

                        Textarea::make('description')
                            ->disabled()
                            ->label('Опис')
                            ->rows(3),

                        TextInput::make('source_type')
                            ->disabled()
                            ->label('Тип джерела')
                            ->helperText('Модель, яка спровокувала цю операцію'),

                        TextInput::make('created_at')
                            ->disabled()
                            ->label('Дата операції')
                            ->formatStateUsing(fn ($state) => is_string($state) ? $state : ($state?->format('d.m.Y H:i:s') ?? '')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')
                    ->label('Користувач')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Тип операції')
                    ->formatStateUsing(fn (LoyaltyTransaction $record) => $record->getTypeLabel())
                    ->color(fn (LoyaltyTransaction $record) => $record->getTypeColor())
                    ->icon(fn (LoyaltyTransaction $record) => $record->getTypeIcon())
                    ->sortable(),

                TextColumn::make('points_amount')
                    ->label('Бали')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').number_format($state, 0, '.', ' ')),

                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('source_type')
                    ->label('Джерело')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '–')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип операції')
                    ->options([
                        'earn' => 'Накопичено',
                        'redeem' => 'Витрачено',
                        'manual_add' => 'Додано вручну',
                        'manual_remove' => 'Видалено вручну',
                        'expire' => 'Закінчилися',
                        'admin_adjustment' => 'Коригування',
                    ]),

                SelectFilter::make('user')
                    ->relationship('user', 'email')
                    ->label('Користувач'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListLoyaltyTransactions::route('/'),
            'create' => Pages\CreateLoyaltyTransaction::route('/create'),
            'edit' => Pages\EditLoyaltyTransaction::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyBalanceResource\Pages;
use App\Filament\Resources\LoyaltyBalanceResource\RelationManagers;
use App\Models\LoyaltyBalance;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class LoyaltyBalanceResource extends Resource
{
    protected static ?string $model = LoyaltyBalance::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Лояльність';

    protected static ?string $navigationLabel = 'Баланси балів';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Баланс балів';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Баланси балів';
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
                            ->disabled(fn (?LoyaltyBalance $record) => $record !== null)
                            ->searchable()
                            ->preload()
                            ->label('Користувач')
                            ->helperText('Виберіть користувача для цього балансу лояльності'),

                        TextInput::make('points')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->label('Поточні бали')
                            ->helperText('Показує всі поточні накопичені бали лояльності'),

                        TextInput::make('lifetime_points')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->label('Бали на всім часу')
                            ->helperText('Загальна сума всіх накопичених балів (включаючи витрачені)'),
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

                TextColumn::make('user.name')
                    ->label("Ім'я користувача")
                    ->searchable(),

                TextColumn::make('points')
                    ->label('Поточні бали')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, '.', ' ')),

                TextColumn::make('lifetime_points')
                    ->label('Бали на всім часу')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, '.', ' ')),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('points_status')
                    ->label('Статус')
                    ->options([
                        'active' => 'З балами',
                        'inactive' => 'Без балів',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'active' => $query->where('points', '>', 0),
                        'inactive' => $query->where('points', '=', 0),
                        default => $query,
                    }),
            ])
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
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyBalances::route('/'),
            'create' => Pages\CreateLoyaltyBalance::route('/create'),
            'edit' => Pages\EditLoyaltyBalance::route('/{record}/edit'),
            'manage' => Pages\ManageLoyaltyPoints::route('/manage-points'),
        ];
    }
}

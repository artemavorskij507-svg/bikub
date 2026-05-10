<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommunityPointsBalanceResource\Pages;
use App\Filament\Resources\CommunityPointsBalanceResource\RelationManagers;
use App\Models\CommunityPointsBalance;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class CommunityPointsBalanceResource extends Resource
{
    protected static ?string $model = CommunityPointsBalance::class;

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?int $navigationSort = 702;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Баллы сообщества';

    protected static ?string $modelLabel = 'Баланс баллов';

    protected static ?string $pluralModelLabel = 'Балансы баллов';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('helper_profile_id')
                    ->label('Помощник')
                    ->relationship('helperProfile', 'display_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('balance_points')
                    ->label('Текущий баланс')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('lifetime_points')
                    ->label('Всего заработано')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('helperProfile.display_name')
                    ->label('Помощник')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('helperProfile.level')
                    ->label('Уровень')
                    ->colors([
                        'success' => 'SOCIAL_HELPER',
                        'warning' => 'COMMUNITY_PARTNER',
                        'info' => 'BIKUBE_FRIEND',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'SOCIAL_HELPER' => 'Social Helper',
                        'COMMUNITY_PARTNER' => 'Community Partner',
                        'BIKUBE_FRIEND' => 'Bikube Friend',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('balance_points')
                    ->label('Текущий баланс')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' '))
                    ->sortable(),
                Tables\Columns\TextColumn::make('lifetime_points')
                    ->label('Всего заработано')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' '))
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListCommunityPointsBalances::route('/'),
            'view' => Pages\ViewCommunityPointsBalance::route('/{record}'),
        ];
    }
}

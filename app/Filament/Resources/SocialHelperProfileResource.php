<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialHelperProfileResource\Pages;
use App\Models\SocialHelperProfile;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class SocialHelperProfileResource extends Resource
{
    protected static ?string $model = SocialHelperProfile::class;

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?int $navigationSort = 704;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Помощники';

    protected static ?string $modelLabel = 'Помощник';

    protected static ?string $pluralModelLabel = 'Помощники';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основное')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('level')
                            ->label('Уровень')
                            ->options([
                                'SOCIAL_HELPER' => 'Social Helper',
                                'COMMUNITY_PARTNER' => 'Community Partner',
                                'BIKUBE_FRIEND' => 'Bikube Friend',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('display_name')
                            ->label('Отображаемое имя')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('bio')
                            ->label('Биография')
                            ->rows(3),
                        Forms\Components\TagsInput::make('skills')
                            ->label('Навыки')
                            ->placeholder('Добавить навык'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Организация')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Организация')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->collapsible(),
                Forms\Components\Section::make('Безопасность и обучение')
                    ->schema([
                        Forms\Components\Toggle::make('has_police_certificate')
                            ->label('Есть справка о несудимости')
                            ->default(false),
                        Forms\Components\DateTimePicker::make('police_certificate_verified_at')
                            ->label('Дата проверки справки')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('first_aid_trained_at')
                            ->label('Дата обучения первой помощи')
                            ->nullable(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Forms\Components\Section::make('Статус и рейтинг')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\TextInput::make('rating_avg')
                            ->label('Средний рейтинг')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('rating_count')
                            ->label('Количество оценок')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TimePicker::make('available_from')
                            ->label('Доступен с')
                            ->nullable(),
                        Forms\Components\TimePicker::make('available_to')
                            ->label('Доступен до')
                            ->nullable(),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('level')
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
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rating_avg')
                    ->label('Рейтинг')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2, ',', ' ') : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating_count')
                    ->label('Оценок')
                    ->formatStateUsing(fn ($state) => $state ?? 0)
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_police_certificate')
                    ->label('Справка')
                    ->boolean(),
                Tables\Columns\TextColumn::make('police_certificate_verified_at')
                    ->label('Проверка справки')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Уровень')
                    ->options([
                        'SOCIAL_HELPER' => 'Social Helper',
                        'COMMUNITY_PARTNER' => 'Community Partner',
                        'BIKUBE_FRIEND' => 'Bikube Friend',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
                Tables\Filters\TernaryFilter::make('has_police_certificate')
                    ->label('Есть справка о несудимости'),
            ])
            ->actions([
                Tables\Actions\Action::make('activate')
                    ->action(function (SocialHelperProfile $record) {
                        $record->update(['is_active' => true]);
                    })
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (SocialHelperProfile $record) {
                        $record->update(['is_active' => true]);
                    })
                    ->visible(fn (SocialHelperProfile $record) => ! $record->is_active),
                Tables\Actions\Action::make('deactivate')
                    ->action(function (SocialHelperProfile $record) {
                        $record->update(['is_active' => false]);
                    })
                    ->label('Деактивировать')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (SocialHelperProfile $record) {
                        $record->update(['is_active' => false]);
                    })
                    ->visible(fn (SocialHelperProfile $record) => $record->is_active),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialHelperProfiles::route('/'),
            'create' => Pages\CreateSocialHelperProfile::route('/create'),
            'view' => Pages\ViewSocialHelperProfile::route('/{record}'),
            'edit' => Pages\EditSocialHelperProfile::route('/{record}/edit'),
        ];
    }
}

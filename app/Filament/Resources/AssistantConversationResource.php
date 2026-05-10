<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssistantConversationResource\Pages;
use App\Models\AssistantConversation;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class AssistantConversationResource extends Resource
{
    protected static ?string $model = AssistantConversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Коммуникации';

    protected static ?string $navigationLabel = 'Assistant Conversations';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('creator')
            ->withCount('messages');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Название беседы'),

                Forms\Components\Select::make('channel')
                    ->label('Канал')
                    ->options([
                        'courier' => 'Курьер',
                        'admin' => 'Администратор',
                        'order' => 'Заказ',
                        'support' => 'Поддержка',
                    ])
                    ->default('courier')
                    ->required(),

                Forms\Components\Select::make('created_by')
                    ->label('Создатель')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\Select::make('subject_type')
                    ->label('Тип субъекта')
                    ->options([
                        'App\Models\Order' => 'Заказ',
                        'App\Models\User' => 'Пользователь',
                        'App\Models\Task' => 'Задача',
                    ])
                    ->default(\App\Models\User::class)
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('subject_id')
                    ->label('ID субъекта')
                    ->numeric()
                    ->default(fn () => auth()->id())
                    ->required()
                    ->visible(fn ($get) => $get('subject_type')),

                Forms\Components\Placeholder::make('messages_count')
                    ->label('Количество сообщений')
                    ->content(fn ($record) => $record ? $record->messages()->count() : 0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                BadgeColumn::make('channel')
                    ->label('Канал')
                    ->colors([
                        'primary' => 'courier',
                        'success' => 'admin',
                        'warning' => 'order',
                        'danger' => 'support',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'courier' => 'Курьер',
                        'admin' => 'Администратор',
                        'order' => 'Заказ',
                        'support' => 'Поддержка',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Создатель')
                    ->sortable()
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Тип субъекта')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Сообщений')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Канал')
                    ->options([
                        'courier' => 'Курьер',
                        'admin' => 'Администратор',
                        'order' => 'Заказ',
                        'support' => 'Поддержка',
                    ]),

                Tables\Filters\Filter::make('has_messages')
                    ->label('С сообщениями')
                    ->query(fn (Builder $query): Builder => $query->has('messages')),

                Tables\Filters\Filter::make('no_messages')
                    ->label('Без сообщений')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('messages')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAssistantConversations::route('/'),
            'create' => Pages\CreateAssistantConversation::route('/create'),
            'view' => Pages\ViewAssistantConversation::route('/{record}'),
            'edit' => Pages\EditAssistantConversation::route('/{record}/edit'),
        ];
    }
}

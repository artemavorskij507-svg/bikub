<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Payout;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Выплаты';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Работник')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма')
                            ->numeric()
                            ->step('0.01')
                            ->required(),

                        Forms\Components\TextInput::make('currency')
                            ->label('Валюта')
                            ->default('NOK')
                            ->maxLength(3)
                            ->required(),

                        Forms\Components\Select::make('method')
                            ->label('Способ выплаты')
                            ->options([
                                'vipps' => 'Vipps',
                                'bank' => 'Банковский перевод',
                                'cash' => 'Наличные',
                                'other' => 'Другое',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает подтверждения',
                                'processing' => 'В обработке',
                                'paid' => 'Оплачено',
                                'completed' => 'Завершено',
                                'rejected' => 'Отклонено',
                                'cancelled' => 'Отменено',
                            ])
                            ->default('pending')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Комментарии')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Комментарий работника')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('admin_note')
                            ->label('Комментарий администратора')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Работник')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->sortable()
                    ->money(fn ($record) => $record->currency ?? 'NOK'),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Валюта')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => fn ($state) => in_array($state, ['pending', 'processing']),
                        'success' => fn ($state) => in_array($state, ['paid', 'completed']),
                        'danger' => fn ($state) => in_array($state, ['rejected', 'cancelled']),
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'pending' => 'Ожидает',
                            'processing' => 'В обработке',
                            'paid' => 'Оплачено',
                            'completed' => 'Завершено',
                            'rejected' => 'Отклонено',
                            'cancelled' => 'Отменено',
                            default => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('method')
                    ->label('Способ')
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            'vipps' => 'Vipps',
                            'bank' => 'Банк',
                            'cash' => 'Наличные',
                            'other' => 'Другое',
                            default => $state ?? '—',
                        };
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Обработано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'processing' => 'В обработке',
                        'paid' => 'Оплачено',
                        'completed' => 'Завершено',
                        'rejected' => 'Отклонено',
                        'cancelled' => 'Отменено',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }

    /** Ограничим создание только для админов/бухгалтеров (если нужно) */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'operator', 'accountant']);
        }

        // Если метод hasAnyRole не существует, проверяем через roles()
        if (method_exists($user, 'roles')) {
            return $user->roles()->whereIn('name', ['admin', 'operator', 'accountant'])->exists();
        }

        return true;
    }
}

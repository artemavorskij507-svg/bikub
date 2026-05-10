<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarePlanResource\Pages;
use App\Models\CarePlan;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CarePlanResource extends Resource
{
    protected static ?string $model = CarePlan::class;

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?int $navigationSort = 703;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Планы заботы';

    protected static ?string $modelLabel = 'План заботы';

    protected static ?string $pluralModelLabel = 'Планы заботы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Клиент и контакт')
                    ->schema([
                        Forms\Components\Select::make('client_profile_id')
                            ->label('Клиент')
                            ->relationship('clientProfile', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('trusted_contact_id')
                            ->label('Доверенное лицо')
                            ->relationship('trustedContact', 'full_name', fn ($query, $get) => $query->where('client_profile_id', $get('client_profile_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Услуга и частота')
                    ->schema([
                        Forms\Components\Select::make('care_service_id')
                            ->label('Услуга')
                            ->relationship('careService', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('frequency')
                            ->label('Частота')
                            ->options([
                                'DAILY' => 'Ежедневно',
                                'WEEKLY' => 'Еженедельно',
                                'BIWEEKLY' => 'Раз в две недели',
                                'MONTHLY' => 'Ежемесячно',
                                'CUSTOM' => 'Индивидуально',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('day_of_week')
                            ->label('День недели')
                            ->options([
                                0 => 'Понедельник',
                                1 => 'Вторник',
                                2 => 'Среда',
                                3 => 'Четверг',
                                4 => 'Пятница',
                                5 => 'Суббота',
                                6 => 'Воскресенье',
                            ])
                            ->nullable()
                            ->visible(fn ($get) => in_array($get('frequency'), ['WEEKLY', 'BIWEEKLY'])),
                        Forms\Components\TimePicker::make('time_of_day')
                            ->label('Время начала')
                            ->nullable(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (минуты)')
                            ->numeric()
                            ->required()
                            ->default(60),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Предпочтения по помощнику')
                    ->schema([
                        Forms\Components\Select::make('preferred_helper_level')
                            ->label('Предпочитаемый уровень помощника')
                            ->options([
                                'SOCIAL_HELPER' => 'Social Helper',
                                'COMMUNITY_PARTNER' => 'Community Partner',
                                'BIKUBE_FRIEND' => 'Bikube Friend',
                            ])
                            ->nullable(),
                        Forms\Components\Select::make('preferred_helper_id')
                            ->label('Предпочитаемый помощник')
                            ->relationship('preferredHelper', 'display_name', function ($query, $get) {
                                if ($get('preferred_helper_level')) {
                                    $query->where('level', $get('preferred_helper_level'));
                                }

                                return $query->where('is_active', true);
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Forms\Components\Section::make('Срок действия')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Начало')
                            ->required(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Окончание')
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'ACTIVE' => 'Активен',
                                'PAUSED' => 'Приостановлен',
                                'CANCELLED' => 'Отменён',
                                'COMPLETED' => 'Завершён',
                            ])
                            ->required()
                            ->default('ACTIVE'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Заметки')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки')
                            ->rows(3),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clientProfile.full_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('careService.name')
                    ->label('Услуга')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Частота')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DAILY' => 'Ежедневно',
                        'WEEKLY' => 'Еженедельно',
                        'BIWEEKLY' => 'Раз в две недели',
                        'MONTHLY' => 'Ежемесячно',
                        'CUSTOM' => 'Индивидуально',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_of_day')
                    ->label('Время')
                    ->time('H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'ACTIVE',
                        'warning' => 'PAUSED',
                        'gray' => ['CANCELLED', 'COMPLETED'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'Активен',
                        'PAUSED' => 'Приостановлен',
                        'CANCELLED' => 'Отменён',
                        'COMPLETED' => 'Завершён',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('careOrders_count')
                    ->label('Визитов')
                    ->counts('careOrders')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('preferredHelper.display_name')
                    ->label('Предпочитаемый помощник')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options([
                        'ACTIVE' => 'Активен',
                        'PAUSED' => 'Приостановлен',
                        'CANCELLED' => 'Отменён',
                        'COMPLETED' => 'Завершён',
                    ]),
                Tables\Filters\SelectFilter::make('frequency')
                    ->label('Частота')
                    ->multiple()
                    ->options([
                        'DAILY' => 'Ежедневно',
                        'WEEKLY' => 'Еженедельно',
                        'BIWEEKLY' => 'Раз в две недели',
                        'MONTHLY' => 'Ежемесячно',
                        'CUSTOM' => 'Индивидуально',
                    ]),
                Tables\Filters\Filter::make('starts_at')
                    ->label('Дата начала')
                    ->form([
                        Forms\Components\DatePicker::make('starts_from')
                            ->label('От'),
                        Forms\Components\DatePicker::make('starts_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['starts_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['starts_until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('active_plans')
                    ->label('Активные планы')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'ACTIVE')
                        ->where('starts_at', '<=', now())
                        ->where(function ($q) {
                            $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        })
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CarePlan $record) => $record->status !== 'ACTIVE')
                    ->action(function (CarePlan $record) {
                        $record->update(['status' => 'ACTIVE']);
                        \Filament\Notifications\Notification::make()
                            ->title('План активирован')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('pause')
                    ->label('Приостановить')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (CarePlan $record) => $record->status === 'ACTIVE')
                    ->action(function (CarePlan $record) {
                        $record->update(['status' => 'PAUSED']);
                        \Filament\Notifications\Notification::make()
                            ->title('План приостановлен')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'care_plans_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Клиент', 'Услуга', 'Частота', 'Время', 'Длительность', 'Статус', 'Начало', 'Окончание', 'Помощник']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->clientProfile->full_name ?? '—',
                                    $record->careService->name ?? '—',
                                    $record->frequency,
                                    $record->time_of_day ? $record->time_of_day->format('H:i') : '—',
                                    $record->duration_minutes.' мин',
                                    $record->status,
                                    $record->starts_at ? $record->starts_at->format('Y-m-d H:i:s') : '—',
                                    $record->ends_at ? $record->ends_at->format('Y-m-d H:i:s') : '—',
                                    $record->preferredHelper->display_name ?? '—',
                                ]);
                            }
                            fclose($file);
                        };

                        return response()->stream($callback, 200, $headers);
                    }),
                Tables\Actions\BulkAction::make('change_status')
                    ->label('Изменить статус')
                    ->icon('heroicon-o-refresh')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Новый статус')
                            ->options([
                                'ACTIVE' => 'Активен',
                                'PAUSED' => 'Приостановлен',
                                'CANCELLED' => 'Отменён',
                                'COMPLETED' => 'Завершён',
                            ])
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $record->update(['status' => $data['status']]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Статус изменен')
                            ->body('Обновлено планов: '.$records->count())
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->update(['status' => 'ACTIVE']);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Планы активированы')
                            ->body('Обновлено: '.$records->count())
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListCarePlans::route('/'),
            'create' => Pages\CreateCarePlan::route('/create'),
            'view' => Pages\ViewCarePlan::route('/{record}'),
            'edit' => Pages\EditCarePlan::route('/{record}/edit'),
        ];
    }
}

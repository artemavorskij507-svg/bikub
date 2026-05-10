<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HandymanAssignmentResource\Pages;
use App\Models\HandymanAssignment;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class HandymanAssignmentResource extends Resource
{
    protected static ?string $model = HandymanAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Handyman & Repair';

    protected static ?int $navigationSort = 402;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Заказ и мастер')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Заказ')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    return;
                                }
                                $order = Order::with('repairProject')->find($state);
                                if (! $order) {
                                    return;
                                }
                                // Автоподстановка проекта ремонта, если он связан с заказом.
                                if ($order->repairProject?->id) {
                                    $set('repair_project_id', $order->repairProject->id);
                                }
                                // Если нет планируемого начала, берём дату из заказа.
                                if ($order->scheduled_at) {
                                    $set('planned_start_at', $order->scheduled_at);
                                }
                            })
                            ->helperText('Заказ, к которому относится назначение. Для уже созданных записей изменить нельзя.'),
                        Forms\Components\Select::make('executor_profile_id')
                            ->label('Мастер')
                            ->relationship('executorProfile', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name ?? "Профиль #{$record->id}")
                            ->searchable()
                            ->required()
                            ->helperText('Мастер, который будет выполнять работы по этому назначению.'),
                        Forms\Components\Select::make('repair_project_id')
                            ->label('Проект ремонта')
                            ->relationship('repairProject', 'title')
                            ->searchable()
                            ->nullable()
                            ->helperText('Опционально: привязка к проекту ремонта, если он существует.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Статус и сроки')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'proposed' => 'Предложено',
                                'accepted' => 'Принято',
                                'declined' => 'Отклонено',
                                'reassigned' => 'Переназначено',
                                'cancelled' => 'Отменено',
                                'completed' => 'Завершено',
                            ])
                            ->required()
                            ->default('proposed')
                            ->helperText('Текущее состояние назначения для мастера.'),
                        Forms\Components\DateTimePicker::make('planned_start_at')
                            ->label('Планируемое начало')
                            ->nullable()
                            ->helperText('Когда планируется начать работы.'),
                        Forms\Components\DateTimePicker::make('planned_finish_at')
                            ->label('Планируемое окончание')
                            ->nullable()
                            ->helperText('Предполагаемое время завершения работ.'),
                        Forms\Components\DateTimePicker::make('actual_start_at')
                            ->label('Фактическое начало')
                            ->nullable()
                            ->helperText('Заполняется, когда мастер фактически приступил к работе.'),
                        Forms\Components\DateTimePicker::make('actual_finish_at')
                            ->label('Фактическое окончание')
                            ->nullable()
                            ->helperText('Заполняется при фактическом завершении работ.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Приоритет и метаданные')
                    ->schema([
                        Forms\Components\TextInput::make('score')
                            ->label('Score / приоритет')
                            ->numeric()
                            ->nullable()
                            ->helperText('Опциональный числовой приоритет или внутренний рейтинг назначения.'),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Основное назначение')
                            ->default(true)
                            ->helperText('Отметьте, если это основное назначение по заказу.'),
                        Forms\Components\KeyValue::make('meta')
                            ->label('Метаданные')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение')
                            ->nullable()
                            ->helperText('Служебные данные для интеграций, заметок и аналитики.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Заказ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('executorProfile.user.name')
                    ->label('Мастер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'proposed',
                        'success' => 'accepted',
                        'danger' => 'declined',
                        'secondary' => 'reassigned',
                        'gray' => 'cancelled',
                        'primary' => 'completed',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('planned_start_at')
                    ->label('Планируемое начало')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('planned_finish_at')
                    ->label('Планируемое окончание')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Основное')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'proposed' => 'Предложено',
                        'accepted' => 'Принято',
                        'declined' => 'Отклонено',
                        'reassigned' => 'Переназначено',
                        'cancelled' => 'Отменено',
                        'completed' => 'Завершено',
                    ]),
                Tables\Filters\SelectFilter::make('executor_profile_id')
                    ->label('Мастер')
                    ->relationship('executorProfile', 'id')
                    ->options(fn () => \App\Models\Moving\ExecutorProfile::query()
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn ($profile) => [$profile->id => $profile->user?->name ?? "Профиль #{$profile->id}"])
                        ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('accept')
                    ->label('Принять')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (HandymanAssignment $record) {
                        /** @var \App\Services\Handyman\HandymanAssignmentService $service */
                        $service = app(\App\Services\Handyman\HandymanAssignmentService::class);
                        $service->acceptAssignment($record);
                    })
                    ->visible(fn (HandymanAssignment $record) => $record->status === 'proposed'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Экспорт (CSV)')
                    ->icon('heroicon-o-download')
                    ->action(function ($records) {
                        $filename = 'handyman_assignments_'.now()->format('Y-m-d_H-i-s').'.csv';
                        $headers = [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ];
                        $callback = function () use ($records) {
                            $file = fopen('php://output', 'w');
                            fputcsv($file, ['ID', 'Заказ', 'Мастер', 'Статус', 'Планируемое начало', 'Планируемое окончание', 'Фактическое начало', 'Фактическое окончание', 'Score', 'Основное']);
                            foreach ($records as $record) {
                                fputcsv($file, [
                                    $record->id,
                                    $record->order->order_number ?? '—',
                                    $record->executorProfile->user->name ?? '—',
                                    $record->status,
                                    $record->planned_start_at ? $record->planned_start_at->format('Y-m-d H:i:s') : '—',
                                    $record->planned_finish_at ? $record->planned_finish_at->format('Y-m-d H:i:s') : '—',
                                    $record->actual_start_at ? $record->actual_start_at->format('Y-m-d H:i:s') : '—',
                                    $record->actual_finish_at ? $record->actual_finish_at->format('Y-m-d H:i:s') : '—',
                                    $record->score ?? '—',
                                    $record->is_primary ? 'Да' : 'Нет',
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
                                'proposed' => 'Предложено',
                                'accepted' => 'Принято',
                                'declined' => 'Отклонено',
                                'reassigned' => 'Переназначено',
                                'cancelled' => 'Отменено',
                                'completed' => 'Завершено',
                            ])
                            ->required(),
                    ])
                    ->action(function ($records, array $data) {
                        foreach ($records as $record) {
                            $record->update(['status' => $data['status']]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Статус изменен')
                            ->body('Обновлено назначений: '.$records->count())
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
            'index' => Pages\ListHandymanAssignments::route('/'),
            'create' => Pages\CreateHandymanAssignment::route('/create'),
            'edit' => Pages\EditHandymanAssignment::route('/{record}/edit'),
        ];
    }
}

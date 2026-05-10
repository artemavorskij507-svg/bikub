<?php

namespace App\Filament\Resources;

use App\Models\ScheduleSlot;
use Filament\Forms\Components as F;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns as C;
use Filament\Tables\Filters;
use Illuminate\Database\Eloquent\Builder;

class ScheduleSlotResource extends Resource
{
    protected static ?string $model = ScheduleSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    // TODO fixed by Cursor: normalize navigation group name to unified 'Ресурсы и планирование'
    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?string $navigationLabel = 'Временные слоты';

    protected static ?int $navigationSort = 105;

    public static function form(Form $form): Form
    {
        return $form->schema([
            F\Section::make('Основное')->schema([
                F\Select::make('zone_id')->relationship('zone', 'name')->searchable()->required(),
                F\Select::make('service_type_id')->relationship('serviceType', 'name')->searchable()->nullable(),
                F\Select::make('kind')->options([
                    'delivery' => 'Доставка', 'pickup' => 'Забор', 'service' => 'Выезд сервис', 'shuttle' => 'Шаттл',
                ])->required(),
                F\DateTimePicker::make('start_at')->required(),
                F\DateTimePicker::make('end_at')->required()->rule('after:start_at'),
                F\Toggle::make('hard_window')->label('Жёсткое окно')->helperText('запрет автосмещений'),
                F\Grid::make(3)->schema([
                    F\TextInput::make('buffer_before_min')->numeric()->minValue(0)->suffix('мин'),
                    F\TextInput::make('buffer_after_min')->numeric()->minValue(0)->suffix('мин'),
                    F\Select::make('status')->options(['open' => 'open', 'hold' => 'hold', 'locked' => 'locked', 'closed' => 'closed']),
                ]),
            ])->columns(2),
            F\Section::make('Вместимость')->schema([
                F\TextInput::make('capacity_total')->numeric()->minValue(1)->required(),
                F\TextInput::make('capacity_reserved')->numeric()->minValue(0)->disabled(),
                F\TextInput::make('capacity_confirmed')->numeric()->minValue(0)->disabled(),
                F\TextInput::make('max_orders')->numeric()->minValue(0)->helperText('0 = без ограничения'),
                F\TextInput::make('courier_required')->numeric()->minValue(1)->suffix('чел'),
                F\TextInput::make('courier_assigned')->numeric()->minValue(0)->suffix('чел')->disabled(),
            ])->columns(3),
            F\Section::make('Ограничения и фичи')->schema([
                F\TextInput::make('max_distance_km')->numeric()->suffix('км'),
                F\TagsInput::make('features')->placeholder('refrigerated, two_persons...'),
                F\KeyValue::make('meta')->label('Meta'),
            ])->columns(2)->collapsed(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            C\TextColumn::make('start_at')
                ->dateTime('MMM d, HH:mm')
                ->sortable()
                ->label('Старт'),
            C\TextColumn::make('end_at')
                ->dateTime('HH:mm')
                ->label('Финиш'),
            C\BadgeColumn::make('zone.name')
                ->label('Зона'),
            C\BadgeColumn::make('kind')
                ->label('Тип')
                ->colors([
                    'info' => 'pickup',
                    'success' => 'delivery',
                    'warning' => 'service',
                    'gray' => 'shuttle',
                ]),
            C\TextColumn::make('serviceType.name')
                ->label('Услуга')
                ->toggleable(),
            C\BadgeColumn::make('status')
                ->colors([
                    'success' => 'open',
                    'warning' => 'hold',
                    'info' => 'locked',
                    'gray' => 'closed',
                ]),
            C\TextColumn::make('capacity_total')
                ->label('Всего'),
            C\TextColumn::make('capacity_reserved')
                ->label('Hold'),
            C\TextColumn::make('capacity_confirmed')
                ->label('Подтв.'),
            C\BadgeColumn::make('capacityFree')
                ->label('Свободно')
                ->colors([
                    'danger' => fn ($state) => $state === 0,
                    'warning' => fn ($state) => $state !== null && $state > 0 && $state <= 2,
                    'success' => fn ($state) => $state !== null && $state > 2,
                ]),
            // Улучшение: визуализация загрузки и овербукинга
            C\TextColumn::make('utilization')
                ->label('Загрузка')
                ->getStateUsing(function (ScheduleSlot $record) {
                    $total = (int) ($record->capacity_total ?? 0);
                    if ($total <= 0) {
                        return '—';
                    }

                    $free = (int) ($record->capacityFree ?? 0);
                    $used = max(0, $total - $free);
                    $percent = round(100 * $used / $total);

                    return "{$used} / {$total} ({$percent}%)";
                })
                ->toggleable(),
            C\IconColumn::make('overbooked')
                ->label('Овербукинг')
                ->boolean()
                ->getStateUsing(fn (ScheduleSlot $record) => $record->isOverbooked())
                ->trueColor('danger')
                ->falseColor('gray')
                ->toggleable(),
        ])->filters([
            Filters\Filter::make('date')->form([
                F\DatePicker::make('from'), F\DatePicker::make('to'),
            ])->query(function (Builder $q, array $data) {
                return $q
                    ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('start_at', '>=', $d))
                    ->when($data['to'] ?? null, fn ($qq, $d) => $qq->whereDate('start_at', '<=', $d));
            }),
            Filters\SelectFilter::make('zone_id')->relationship('zone', 'name')->label('Зона'),
            Filters\SelectFilter::make('kind')->options([
                'delivery' => 'delivery', 'pickup' => 'pickup', 'service' => 'service', 'shuttle' => 'shuttle',
            ]),
            Filters\SelectFilter::make('status')->options([
                'open' => 'open', 'hold' => 'hold', 'locked' => 'locked', 'closed' => 'closed',
            ]),
            Filters\Filter::make('only_free')
                ->label('Только со свободной ёмкостью')
                ->toggle()
                ->query(fn (Builder $query) => $query->whereRaw('(capacity_total - coalesce(capacity_reserved,0) - coalesce(capacity_confirmed,0)) > 0')),
        ])->headerActions([
            Tables\Actions\Action::make('generateDay')
                ->label('Сгенерировать день')
                ->icon('heroicon-o-sparkles')
                ->form([
                    F\DatePicker::make('date')->required(),
                    F\Select::make('zone_id')->relationship('zone', 'name')->required(),
                    F\Select::make('kind')->options(['delivery' => 'delivery', 'pickup' => 'pickup', 'service' => 'service', 'shuttle' => 'shuttle'])->required(),
                    F\TextInput::make('from')->default('08:00'),
                    F\TextInput::make('to')->default('22:00'),
                    F\TextInput::make('step_min')->numeric()->default(60),
                    F\TextInput::make('capacity_total')->numeric()->default(10),
                ])
                ->action(function (array $data) {
                    try {
                        $date = \Carbon\Carbon::parse($data['date']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Ошибка')
                            ->body('Неверный формат даты: '.($data['date'] ?? 'не указано'))
                            ->danger()
                            ->send();

                        return;
                    }
                    $from = $date->copy()->setTimeFromTimeString($data['from']);
                    $to = $date->copy()->setTimeFromTimeString($data['to']);
                    $step = (int) $data['step_min'];

                    \DB::transaction(function () use ($from, $to, $step, $data) {
                        for ($t = $from->copy(); $t->lt($to); $t->addMinutes($step)) {
                            \App\Models\ScheduleSlot::firstOrCreate([
                                'zone_id' => $data['zone_id'],
                                'kind' => $data['kind'],
                                'start_at' => $t,
                                'end_at' => $t->copy()->addMinutes($step),
                            ], [
                                'capacity_total' => $data['capacity_total'],
                                'status' => 'open',
                            ]);
                        }
                    });
                }),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('lock')->label('Lock')->icon('heroicon-o-lock-closed')
                ->visible(fn ($record) => $record && $record->status !== 'locked')
                ->action(fn (ScheduleSlot $record) => $record->update(['status' => 'locked'])),
            Tables\Actions\Action::make('open')->label('Open')->icon('heroicon-o-lock-open')
                ->visible(fn ($record) => $record && $record->status !== 'open')
                ->action(fn (ScheduleSlot $record) => $record->update(['status' => 'open'])),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('bulkLock')->label('Lock выбранные')
                ->action(fn ($records) => $records->each->update(['status' => 'locked'])),
            Tables\Actions\BulkAction::make('bulkOpen')->label('Open выбранные')
                ->action(fn ($records) => $records->each->update(['status' => 'open'])),
            Tables\Actions\BulkAction::make('shift')->label('Сдвинуть время')
                ->form([F\TextInput::make('minutes')->numeric()->required()])
                ->action(function ($records, array $data) {
                    $m = (int) $data['minutes'];
                    foreach ($records as $record) {
                        $record->update([
                            'start_at' => $record->start_at->copy()->addMinutes($m),
                            'end_at' => $record->end_at->copy()->addMinutes($m),
                        ]);
                    }
                }),
        ])->defaultSort('start_at', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            ScheduleSlotResource\RelationManagers\OrdersRelationManager::class,
            ScheduleSlotResource\RelationManagers\EmployeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ScheduleSlotResource\Pages\ListScheduleSlots::route('/'),
            'create' => ScheduleSlotResource\Pages\CreateScheduleSlot::route('/create'),
            'view' => ScheduleSlotResource\Pages\ViewScheduleSlot::route('/{record}'),
            'edit' => ScheduleSlotResource\Pages\EditScheduleSlot::route('/{record}/edit'),
        ];
    }
}

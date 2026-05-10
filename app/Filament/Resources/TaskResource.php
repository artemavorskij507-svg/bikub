<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\Relations;
use App\Models\Employee;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\ScheduleSlot;
use App\Models\Task;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-check';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?int $navigationSort = 103;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('order_id')
                ->label('Order')
                ->searchable()
                ->preload()
                ->getSearchResultsUsing(function (string $search) {
                    return Order::query()
                        ->when(is_numeric($search), function ($q) use ($search) {
                            $q->orWhere('id', (int) $search);
                        })
                        ->orWhere('order_number', 'ilike', "%{$search}%")
                        ->orderByDesc('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function (Order $o) {
                            $label = ($o->order_number ?: ("#{$o->id}"));

                            return [$o->id => $label];
                        })->toArray();
                })
                ->getOptionLabelUsing(function ($value) {
                    $o = Order::find($value);

                    return $o ? ($o->order_number ?: ("#{$o->id}")) : (string) $value;
                })
                ->options(function () {
                    return Order::query()->orderByDesc('id')->limit(50)->get()->mapWithKeys(function (Order $o) {
                        $label = ($o->order_number ?: ("#{$o->id}"));

                        return [$o->id => $label];
                    })->toArray();
                })
                ->required(),
            Forms\Components\TextInput::make('type')->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'queued' => 'queued',
                    'ready' => 'ready',
                    'assigned' => 'assigned',
                    'en_route' => 'en_route',
                    'arrived' => 'arrived',
                    'in_progress' => 'in_progress',
                    'paused' => 'paused',
                    'completed' => 'completed',
                    'failed' => 'failed',
                    'canceled' => 'canceled',
                    'rescheduled' => 'rescheduled',
                ])->default('queued'),
            Forms\Components\Select::make('priority')
                ->options([
                    'low' => 'low', 'normal' => 'normal', 'high' => 'high', 'urgent' => 'urgent',
                ])->default('normal'),
            Forms\Components\Select::make('assignee_id')
                ->label('Assignee')
                ->options(function () {
                    return Employee::query()
                        ->selectRaw("id, (COALESCE(first_name,'') || ' ' || COALESCE(last_name,'')) as full_name")
                        ->orderBy('full_name')
                        ->pluck('full_name', 'id');
                })
                ->preload(),
            Forms\Components\Select::make('zone_id')
                ->label('Zone')
                ->options(GeoZone::query()->pluck('name', 'id')),
            Forms\Components\Select::make('slot_id')
                ->label('Slot')
                ->options(ScheduleSlot::query()->pluck('id', 'id')),
            Forms\Components\TextInput::make('address_text')->columnSpanFull(),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('lat')->numeric(),
                Forms\Components\TextInput::make('lng')->numeric(),
                Forms\Components\TextInput::make('expected_duration_min')->numeric(),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\DateTimePicker::make('window_start'),
                Forms\Components\DateTimePicker::make('window_end'),
            ]),
            Forms\Components\Toggle::make('proof_required')->default(false),
            Forms\Components\Textarea::make('instructions')->rows(3)->columnSpanFull(),
            Forms\Components\Fieldset::make('requirements_inspector')
                ->label('Requirements')
                ->schema([
                    Forms\Components\TagsInput::make('requirements.skills')->label('Skills'),
                    Forms\Components\Select::make('requirements.vehicle')->label('Vehicle')
                        ->options([
                            'car' => 'Car',
                            'van' => 'Van',
                            'bike' => 'Bike',
                            'foot' => 'On foot',
                        ]),
                    Forms\Components\CheckboxList::make('requirements.equipment')->label('Equipment')
                        ->options([
                            'chains' => 'Chains',
                            'thermal_bag' => 'Thermal bag',
                            'strap' => 'Strap',
                            'trolley' => 'Trolley',
                        ])->columns(2),
                ])->columns(3)->columnSpanFull(),
            Forms\Components\KeyValue::make('requirements')->label('Requirements (raw)')->columnSpanFull(),
            Forms\Components\KeyValue::make('attachments')->columnSpanFull(),
            Forms\Components\KeyValue::make('meta')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('type')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'queued',
                        'info' => 'ready',
                        'warning' => 'assigned',
                        'primary' => 'en_route',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])->sortable(),
                Tables\Columns\TextColumn::make('priority')->sortable(),
                Tables\Columns\TextColumn::make('assignee.full_name')->label('Assignee')->toggleable(),
                Tables\Columns\TextColumn::make('zone.name')->label('Zone')->toggleable(),
                Tables\Columns\TextColumn::make('slot_id')->label('Slot')->toggleable(),
                Tables\Columns\TextColumn::make('window_start')->dateTime()->toggleable(),
                Tables\Columns\TextColumn::make('window_end')->dateTime()->toggleable(),
                Tables\Columns\TextColumn::make('expected_duration_min')->label('ETA min')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'queued', 'ready' => 'ready', 'assigned' => 'assigned',
                        'en_route' => 'en_route', 'arrived' => 'arrived', 'in_progress' => 'in_progress',
                        'paused' => 'paused', 'completed' => 'completed', 'failed' => 'failed',
                        'canceled' => 'canceled', 'rescheduled' => 'rescheduled',
                    ]),
                Tables\Filters\SelectFilter::make('assignee_id')
                    ->label('Assignee')
                    ->options(function () {
                        return Employee::query()
                            ->selectRaw("id, (COALESCE(first_name,'') || ' ' || COALESCE(last_name,'')) as full_name")
                            ->orderBy('full_name')
                            ->pluck('full_name', 'id')->all();
                    }),
                Tables\Filters\SelectFilter::make('zone_id')
                    ->label('Zone')
                    ->options(GeoZone::query()->pluck('name', 'id')->all()),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date'),
                    ])->query(function ($query, array $data) {
                        if (! empty($data['date'])) {
                            $date = $data['date'];
                            $query->whereDate('window_start', '<=', $date)->whereDate('window_end', '>=', $date);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('setStatus')
                    ->label('Set status')
                    ->form([
                        Forms\Components\Select::make('status')->options([
                            'queued' => 'queued', 'ready' => 'ready', 'assigned' => 'assigned', 'en_route' => 'en_route', 'arrived' => 'arrived', 'in_progress' => 'in_progress', 'paused' => 'paused', 'completed' => 'completed', 'failed' => 'failed', 'canceled' => 'canceled', 'rescheduled' => 'rescheduled',
                        ])->required(),
                    ])->action(function (Task $record, array $data) {
                        $record->status = $data['status'];
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assign')
                    ->label('Назначить исполнителя')
                    ->form([
                        Forms\Components\Select::make('assignee_id')->label('Assignee')->options(function () {
                            return Employee::query()
                                ->selectRaw("id, (COALESCE(first_name,'') || ' ' || COALESCE(last_name,'')) as full_name")
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id');
                        })->required(),
                    ])->action(function ($records, array $data) {
                        foreach ($records as $task) {
                            $task->assignee_id = $data['assignee_id'];
                            $task->save();
                        }
                    }),
                Tables\Actions\BulkAction::make('setSlot')
                    ->label('Назначить слот')
                    ->form([
                        Forms\Components\Select::make('slot_id')->label('Slot')->options(ScheduleSlot::query()->pluck('id', 'id'))->required(),
                    ])->action(function ($records, array $data) {
                        foreach ($records as $task) {
                            $task->slot_id = $data['slot_id'];
                            $task->save();
                        }
                    }),
                Tables\Actions\BulkAction::make('shiftWindow')
                    ->label('Сдвинуть окно (мин)')
                    ->form([
                        Forms\Components\TextInput::make('delta')->numeric()->required()->default(30),
                    ])->action(function ($records, array $data) {
                        $delta = (int) ($data['delta'] ?? 0);
                        foreach ($records as $task) {
                            if ($task->window_start) {
                                $task->window_start = $task->window_start->clone()->addMinutes($delta);
                            }
                            if ($task->window_end) {
                                $task->window_end = $task->window_end->clone()->addMinutes($delta);
                            }
                            $task->save();
                        }
                    }),
                Tables\Actions\BulkAction::make('reassignZone')
                    ->label('Переназначить зону')
                    ->form([
                        Forms\Components\Select::make('zone_id')->label('Zone')
                            ->options(GeoZone::query()->pluck('name', 'id')->all())
                            ->required(),
                    ])->action(function ($records, array $data) {
                        foreach ($records as $task) {
                            $task->zone_id = $data['zone_id'];
                            $task->save();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            Relations\TaskEventsRelationManager::class,
        ];
    }
}

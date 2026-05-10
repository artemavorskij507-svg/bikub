<?php

namespace App\Filament\Resources;

use App\Domain\Ops\Queries\OperationExceptionsTableQuery;
use App\Domain\Exceptions\Models\OperationException;
use App\Filament\Resources\OperationExceptionResource\Pages;
use App\Support\Ops\OpsUiPresenter;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class OperationExceptionResource extends Resource
{
    protected static ?string $model = OperationException::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Operation Exceptions';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', OperationException::class) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Main')
                ->schema([
                    Forms\Components\TextInput::make('type')->disabled(),
                    Forms\Components\Select::make('severity')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                            'critical' => 'Critical',
                        ])
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'open' => 'Open',
                            'acknowledged' => 'Acknowledged',
                            'investigating' => 'Investigating',
                            'mitigated' => 'Mitigated',
                            'resolved' => 'Resolved',
                            'dismissed' => 'Dismissed',
                        ]),
                    Forms\Components\TextInput::make('detected_by')->disabled(),
                    Forms\Components\DateTimePicker::make('detected_at')->disabled(),
                    Forms\Components\DateTimePicker::make('acknowledged_at')->disabled(),
                    Forms\Components\DateTimePicker::make('resolved_at')->disabled(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Resolution')
                ->schema([
                    Forms\Components\TextInput::make('root_cause'),
                    Forms\Components\TextInput::make('resolution_code'),
                    Forms\Components\Textarea::make('resolution_notes')->rows(5),
                ]),

            Forms\Components\Section::make('Payload')
                ->schema([
                    Forms\Components\Textarea::make('payload_json')
                        ->label('Payload (JSON)')
                        ->rows(14)
                        ->dehydrated(false)
                        ->disabled()
                        ->afterStateHydrated(static function (Forms\Components\Textarea $component, $state, ?OperationException $record): void {
                            unset($state);

                            $component->state(
                                json_encode(
                                    (array) ($record?->payload ?? []),
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                ) ?: '{}'
                            );
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => OpsUiPresenter::exceptionTypeValue($record))
                    ->formatStateUsing(fn ($record) => ucfirst(OpsUiPresenter::exceptionType($record))),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'secondary' => 'low',
                        'warning' => 'medium',
                        'danger' => ['high', 'critical'],
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'open',
                        'warning' => ['acknowledged', 'investigating', 'mitigated'],
                        'success' => 'resolved',
                        'secondary' => 'dismissed',
                    ]),
                Tables\Columns\TextColumn::make('service_job_id')->label('Job'),
                Tables\Columns\TextColumn::make('executor_id')->label('Executor'),
                Tables\Columns\TextColumn::make('owner_user_id')->label('Owner')->default('-'),
                Tables\Columns\TextColumn::make('sla_metric')
                    ->label('SLA metric')
                    ->getStateUsing(fn ($record) => data_get($record->payload, 'metric_name') ?: '-'),
                Tables\Columns\TextColumn::make('detected_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('service_job_id')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Job ID')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where('service_job_id', (int) $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'acknowledged' => 'Acknowledged',
                        'investigating' => 'Investigating',
                        'mitigated' => 'Mitigated',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'sla_warning' => 'SLA Warning',
                        'sla_breach' => 'SLA Breach',
                        'stale_location_ping' => 'Stale GPS Ping',
                        'assignment_stalled' => 'Assignment Stalled',
                        'work_not_started_after_arrival' => 'Work Not Started After Arrival',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($value): void {
                            $q->where('type', $value)->orWhere('exception_type', $value);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('acknowledge')
                    ->visible(fn ($record) => $record->status === 'open')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => 'acknowledged',
                        'acknowledged_at' => now(),
                        'owner_user_id' => auth()->id(),
                    ])),
                Tables\Actions\Action::make('resolve')
                    ->visible(fn ($record) => in_array($record->status, ['open', 'acknowledged', 'investigating', 'mitigated'], true))
                    ->form([
                        Forms\Components\TextInput::make('resolution_code')->required(),
                        Forms\Components\TextInput::make('root_cause'),
                        Forms\Components\Textarea::make('resolution_notes'),
                    ])
                    ->action(function (OperationException $record, array $data): void {
                        $record->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                            'owner_user_id' => auth()->id(),
                            'resolution_code' => $data['resolution_code'],
                            'root_cause' => $data['root_cause'] ?? null,
                            'resolution_notes' => $data['resolution_notes'] ?? null,
                        ]);
                    }),
                Tables\Actions\Action::make('escalate')
                    ->label('Escalate')
                    ->icon('heroicon-o-arrow-up')
                    ->visible(fn ($record) => in_array($record->status, ['open', 'acknowledged', 'investigating'], true))
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'investigating',
                            'severity' => in_array($record->severity, ['high', 'critical'], true) ? $record->severity : 'high',
                            'owner_user_id' => auth()->id(),
                        ]);
                        Notification::make()->title('Exception escalated')->success()->send();
                    }),
                Tables\Actions\Action::make('reassign')
                    ->label('Reassign')
                    ->icon('heroicon-o-switch-horizontal')
                    ->visible(fn ($record) => ! empty($record->service_job_id))
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        if ($record->serviceJob) {
                            $record->serviceJob->update(['status' => 'pending_dispatch', 'assignment_id' => null, 'executor_id' => null]);
                            dispatch(new \App\Jobs\CalculateDispatchCandidatesJob($record->serviceJob->id));
                            Notification::make()->title('Reassign requested')->success()->send();
                        }
                    }),
                Tables\Actions\Action::make('open_linked_job')
                    ->label('Open linked job')
                    ->icon('heroicon-o-external-link')
                    ->url(fn ($record) => $record->service_job_id ? \App\Filament\Resources\ServiceJobResource::getUrl('view', ['record' => $record->service_job_id]) : null, true)
                    ->visible(fn ($record) => ! empty($record->service_job_id)),
            ])
            ->defaultSort('detected_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(OperationExceptionsTableQuery::class)->builder();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperationExceptions::route('/'),
            'view' => Pages\ViewOperationException::route('/{record}'),
        ];
    }
}

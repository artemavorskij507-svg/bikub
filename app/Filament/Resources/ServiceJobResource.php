<?php

namespace App\Filament\Resources;

use App\Domain\Ops\Queries\ServiceJobsTableQuery;
use App\Domain\Operations\Models\ServiceJob;
use App\Filament\Resources\ServiceJobResource\Pages;
use App\Support\Ops\JobStatusPresenter;
use App\Support\Ops\OpsUiPresenter;
use App\Support\Ops\SlaLabelPresenter;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ServiceJobResource extends Resource
{
    protected static ?string $model = ServiceJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Операции';
    protected static ?string $navigationLabel = 'Service Jobs';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', ServiceJob::class) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Main')
                ->schema([
                    Forms\Components\TextInput::make('source_type')->disabled(),
                    Forms\Components\TextInput::make('source_id')->disabled(),
                    Forms\Components\Select::make('service_domain')
                        ->options([
                            'delivery' => 'Delivery',
                            'handyman' => 'Handyman',
                            'moving' => 'Moving',
                            'roadside' => 'Roadside',
                            'social_care' => 'Social Care',
                        ])
                        ->disabled(),
                    Forms\Components\Select::make('job_kind')
                        ->options([
                            'shipment' => 'Shipment',
                            'visit' => 'Visit',
                            'crew_move' => 'Crew Move',
                            'emergency' => 'Emergency',
                            'errand' => 'Errand',
                        ])
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'pending_dispatch' => 'Pending Dispatch',
                            'dispatching' => 'Dispatching',
                            'assigned' => 'Assigned',
                            'en_route' => 'En Route',
                            'arrived' => 'Arrived',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                            'failed' => 'Failed',
                        ]),
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'normal' => 'Normal',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                            'emergency' => 'Emergency',
                        ]),
                ])
                ->columns(3),

            Forms\Components\Section::make('Timing')
                ->schema([
                    Forms\Components\DateTimePicker::make('time_window_start'),
                    Forms\Components\DateTimePicker::make('time_window_end'),
                    Forms\Components\TextInput::make('service_duration_minutes')->numeric(),
                    Forms\Components\DateTimePicker::make('promised_eta_at'),
                    Forms\Components\DateTimePicker::make('promised_completion_at'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Job ID')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('service_domain')->label('Domain')->colors(['primary']),
                Tables\Columns\TextColumn::make('job_kind')->label('Kind')->sortable(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'normal',
                        'warning' => 'high',
                        'danger' => ['urgent', 'emergency'],
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state) => JobStatusPresenter::label($state))
                    ->colors([
                        'secondary' => ['draft', 'pending_dispatch'],
                        'primary' => ['assigned', 'en_route', 'arrived', 'in_progress'],
                        'success' => ['completed'],
                        'danger' => ['cancelled', 'failed'],
                    ]),
                Tables\Columns\TextColumn::make('executor_name')
                    ->label('Executor')
                    ->getStateUsing(fn ($record) => $record->executor?->display_name ?: $record->executor?->name ?: $record->currentAssignment?->executor?->display_name ?: $record->currentAssignment?->executor?->name)
                    ->default('-'),
                Tables\Columns\TextColumn::make('eta')
                    ->label('ETA')
                    ->getStateUsing(fn ($record) => OpsUiPresenter::etaForJob($record) ?: '-'),
                Tables\Columns\BadgeColumn::make('sla_state')
                    ->label('SLA')
                    ->getStateUsing(fn ($record) => SlaLabelPresenter::stateForJob($record))
                    ->formatStateUsing(fn (?string $state) => SlaLabelPresenter::label((string) $state))
                    ->colors([
                        'success' => 'ok',
                        'warning' => 'warning',
                        'danger' => 'breached',
                    ]),
                Tables\Columns\TextColumn::make('exceptions_count')
                    ->label('Exceptions count')
                    ->sortable(),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk')
                    ->getStateUsing(function ($record): int {
                        $slaState = SlaLabelPresenter::stateForJob($record);
                        $exceptionsCount = (int) ($record->exceptions_count ?? 0);

                        return OpsUiPresenter::riskScore($record, $exceptionsCount, $slaState);
                    }),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_domain')
                    ->options([
                        'delivery' => 'Delivery',
                        'handyman' => 'Handyman',
                        'moving' => 'Moving',
                        'roadside' => 'Roadside',
                        'social_care' => 'Social Care',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_dispatch' => 'Pending Dispatch',
                        'assigned' => 'Assigned',
                        'en_route' => 'En Route',
                        'arrived' => 'Arrived',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\Filter::make('at_risk_only')
                    ->label('At risk only')
                    ->query(function (Builder $query): Builder {
                        return $query->where(function (Builder $q): void {
                            $q->whereHas('slaTimers', function (Builder $timerQ): void {
                                $timerQ
                                    ->whereIn('status', ['warning', 'breached'])
                                    ->orWhereIn('dispatch_state', ['warning', 'breached'])
                                    ->orWhereIn('arrival_state', ['warning', 'breached'])
                                    ->orWhereIn('completion_state', ['warning', 'breached']);
                            })->orWhereHas('exceptions', function (Builder $exQ): void {
                                $exQ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated']);
                            });
                        });
                    }),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                        'emergency' => 'Emergency',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('dispatch')
                    ->label('Dispatch')
                    ->icon('heroicon-o-lightning-bolt')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        dispatch(new \App\Jobs\CalculateDispatchCandidatesJob($record->id));
                        Notification::make()->title('Dispatch requested')->success()->send();
                    }),
                Tables\Actions\Action::make('reassign')
                    ->label('Reassign')
                    ->icon('heroicon-o-switch-horizontal')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->update(['status' => 'pending_dispatch', 'assignment_id' => null, 'executor_id' => null]);
                        dispatch(new \App\Jobs\CalculateDispatchCandidatesJob($record->id));
                        Notification::make()->title('Reassign requested')->success()->send();
                    }),
                Tables\Actions\Action::make('open_exceptions')
                    ->label('Open exceptions')
                    ->icon('heroicon-o-exclamation-circle')
                    ->url(fn ($record) => '/admin/operation-exceptions?tableFilters%5Bservice_job_id%5D%5Bvalue%5D='.$record->id),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(ServiceJobsTableQuery::class)->builder();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceJobs::route('/'),
            'view' => Pages\ViewServiceJob::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Enums\CareOrderStatus;
use App\Filament\Resources\ClientProfileResource;
use App\Filament\Resources\SocialCareOrderResource;
use App\Models\CareOrderDetails;
use App\Models\SocialHelperProfile;
use App\Services\SocialCare\CareOrderService;
use App\Services\SocialCare\SocialHelperMatchingService;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class SocialCareDashboard extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments';

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?string $navigationLabel = 'Пульт координатора';

    protected static ?string $title = 'Пульт координатора Social Care';

    protected static ?int $navigationSort = 705;

    protected static string $view = 'filament.pages.social-care-dashboard';

    protected function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\TodayVisitsWidget::class,
            \App\Filament\Widgets\ActiveHelpersWidget::class,
            \App\Filament\Widgets\ClientsUnderCareWidget::class,
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'social-coordinator', 'operator']);
        }

        return true;
    }

    protected function getTableQuery(): Builder
    {
        $now = now();
        $startDate = $now->copy()->subDay();
        $endDate = $now->copy()->addDays(7);

        return CareOrderDetails::query()
            ->with([
                'order',
                'clientProfile',
                'careService',
                'assignedHelper.user',
                'carePlan',
            ])
            ->whereHas('order', function ($q) {
                $q->where(function ($query) {
                    $query->whereHas('careDetails')
                        ->orWhere('metadata->service_type', 'social_care_visit');
                });
            })
            ->whereBetween('scheduled_start_at', [$startDate, $endDate])
            ->orderBy('scheduled_start_at');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('scheduled_start_at')
                ->label('Время')
                ->dateTime('d.m H:i')
                ->sortable(),

            Tables\Columns\BadgeColumn::make('care_status')
                ->label('Статус')
                ->colors([
                    'secondary' => [
                        CareOrderStatus::PENDING->value,
                    ],
                    'info' => [
                        CareOrderStatus::SCHEDULED->value,
                        CareOrderStatus::ACCEPTED_BY_HELPER->value,
                    ],
                    'warning' => [
                        CareOrderStatus::EN_ROUTE->value,
                    ],
                    'primary' => [
                        CareOrderStatus::IN_PROGRESS->value,
                    ],
                    'success' => [
                        CareOrderStatus::COMPLETED->value,
                    ],
                    'danger' => [
                        CareOrderStatus::CANCELLED_BY_CLIENT->value,
                        CareOrderStatus::CANCELLED_BY_OPERATOR->value,
                        CareOrderStatus::CANCELLED_BY_TRUSTED_CONTACT->value,
                        CareOrderStatus::NO_SHOW_CLIENT->value,
                        CareOrderStatus::NO_SHOW_HELPER->value,
                    ],
                ])
                ->formatStateUsing(fn (string $state): string => CareOrderStatus::from($state)->label()),

            Tables\Columns\TextColumn::make('clientProfile.full_name')
                ->label('Клиент')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('clientProfile.city')
                ->label('Город')
                ->sortable(),

            Tables\Columns\TextColumn::make('careService.name')
                ->label('Услуга')
                ->limit(30),

            Tables\Columns\TextColumn::make('assignedHelper.display_name')
                ->label('Помощник')
                ->placeholder('Не назначен')
                ->formatStateUsing(fn ($state, $record) => $state ?
                    $state.' ('.($record->assignedHelper->level ?? '—').')' :
                    'Не назначен'
                ),

            Tables\Columns\BadgeColumn::make('risk_indicator')
                ->label('Риск')
                ->getStateUsing(fn (CareOrderDetails $record) => $this->calculateRisk($record))
                ->colors([
                    'danger' => 'HIGH',
                    'warning' => 'MEDIUM',
                    'success' => 'LOW',
                    'secondary' => 'NONE',
                ])
                ->formatStateUsing(fn ($state) => match ($state) {
                    'HIGH' => 'Высокий',
                    'MEDIUM' => 'Средний',
                    'LOW' => 'Низкий',
                    default => '',
                }),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('scheduled_start_at')
                ->form([
                    Forms\Components\DatePicker::make('date_from')
                        ->label('С даты'),
                    Forms\Components\DatePicker::make('date_to')
                        ->label('По дату'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['date_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', '>=', $date),
                        )
                        ->when(
                            $data['date_to'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', '<=', $date),
                        );
                }),

            Tables\Filters\SelectFilter::make('care_status')
                ->label('Статус')
                ->options([
                    CareOrderStatus::PENDING->value => CareOrderStatus::PENDING->label(),
                    CareOrderStatus::SCHEDULED->value => CareOrderStatus::SCHEDULED->label(),
                    CareOrderStatus::ACCEPTED_BY_HELPER->value => CareOrderStatus::ACCEPTED_BY_HELPER->label(),
                    CareOrderStatus::EN_ROUTE->value => CareOrderStatus::EN_ROUTE->label(),
                    CareOrderStatus::IN_PROGRESS->value => CareOrderStatus::IN_PROGRESS->label(),
                    CareOrderStatus::COMPLETED->value => CareOrderStatus::COMPLETED->label(),
                    CareOrderStatus::CANCELLED_BY_CLIENT->value => CareOrderStatus::CANCELLED_BY_CLIENT->label(),
                    CareOrderStatus::CANCELLED_BY_OPERATOR->value => CareOrderStatus::CANCELLED_BY_OPERATOR->label(),
                    CareOrderStatus::NO_SHOW_CLIENT->value => CareOrderStatus::NO_SHOW_CLIENT->label(),
                    CareOrderStatus::NO_SHOW_HELPER->value => CareOrderStatus::NO_SHOW_HELPER->label(),
                ]),

            Tables\Filters\TernaryFilter::make('has_helper')
                ->label('Назначен помощник')
                ->placeholder('Все')
                ->trueLabel('Назначен')
                ->falseLabel('Не назначен')
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('assigned_helper_id'),
                    false: fn (Builder $query) => $query->whereNull('assigned_helper_id'),
                ),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('assignHelper')
                    ->label('Назначить помощника')
                    ->icon('heroicon-o-user')
                    ->form([
                        Forms\Components\Select::make('helper_id')
                            ->label('Помощник')
                            ->options(
                                SocialHelperProfile::query()
                                    ->where('is_active', true)
                                    ->orderBy('display_name')
                                    ->get()
                                    ->mapWithKeys(fn ($helper) => [
                                        $helper->id => $helper->display_name.' ('.$helper->level.')',
                                    ])
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, CareOrderDetails $record) {
                        $helper = SocialHelperProfile::findOrFail($data['helper_id']);
                        app(CareOrderService::class)->assignHelper(
                            $record->order,
                            $helper,
                            auth()->user()
                        );
                        $this->notify('success', 'Помощник назначен');
                    })
                    ->visible(fn (CareOrderDetails $record) => ! $record->assigned_helper_id),

                Tables\Actions\Action::make('autoAssign')
                    ->label('Авто-назначить')
                    ->icon('heroicon-o-sparkles')
                    ->action(function (CareOrderDetails $record) {
                        $service = app(SocialHelperMatchingService::class);
                        $updatedOrder = $service->autoAssignHelperIfPossible($record->order, auth()->user());

                        if (! $updatedOrder || ! $updatedOrder->careDetails->assigned_helper_id) {
                            $this->notify('warning', 'Не удалось подобрать помощника');

                            return;
                        }

                        $this->notify('success', 'Помощник автоматически назначен');
                    })
                    ->visible(fn (CareOrderDetails $record) => ! $record->assigned_helper_id),

                Tables\Actions\Action::make('markNoShowClient')
                    ->label('No-show клиента')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Комментарий')
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, CareOrderDetails $record) {
                        app(CareOrderService::class)->updateStatus(
                            $record->order,
                            CareOrderStatus::NO_SHOW_CLIENT,
                            auth()->user(),
                            $data['reason'] ?? null
                        );
                        $this->notify('success', 'Визит отмечен как no-show клиента');
                    })
                    ->visible(fn (CareOrderDetails $record) => ! in_array($record->care_status, CareOrderStatus::finalStatuses())
                    ),

                Tables\Actions\Action::make('markNoShowHelper')
                    ->label('No-show помощника')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Комментарий')
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, CareOrderDetails $record) {
                        app(CareOrderService::class)->updateStatus(
                            $record->order,
                            CareOrderStatus::NO_SHOW_HELPER,
                            auth()->user(),
                            $data['reason'] ?? null
                        );
                        $this->notify('success', 'Визит отмечен как no-show помощника');
                    })
                    ->visible(fn (CareOrderDetails $record) => ! in_array($record->care_status, CareOrderStatus::finalStatuses())
                    ),

                Tables\Actions\Action::make('cancelByOperator')
                    ->label('Отменить визит')
                    ->icon('heroicon-o-ban')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина отмены')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data, CareOrderDetails $record) {
                        app(CareOrderService::class)->updateStatus(
                            $record->order,
                            CareOrderStatus::CANCELLED_BY_OPERATOR,
                            auth()->user(),
                            $data['reason']
                        );
                        $this->notify('success', 'Визит отменён');
                    })
                    ->visible(fn (CareOrderDetails $record) => ! in_array($record->care_status, CareOrderStatus::finalStatuses())
                    ),

                Tables\Actions\Action::make('openOrder')
                    ->label('Открыть заказ')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (CareOrderDetails $record) => SocialCareOrderResource::getUrl('view', ['record' => $record->order_id])
                    ),

                Tables\Actions\Action::make('openClient')
                    ->label('Открыть клиента')
                    ->icon('heroicon-o-user')
                    ->url(fn (CareOrderDetails $record) => ClientProfileResource::getUrl('view', ['record' => $record->client_profile_id])
                    )
                    ->visible(fn (CareOrderDetails $record) => $record->client_profile_id),

                Tables\Actions\Action::make('createEcoSubOrder')
                    ->label('Создать эко-вывоз')
                    ->icon('heroicon-o-trash')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('description')
                            ->label('Описание задачи')
                            ->required()
                            ->maxLength(1000)
                            ->helperText('Что нужно вывезти/утилизировать?'),
                    ])
                    ->action(function (array $data, CareOrderDetails $record) {
                        $integrationService = app(\App\Services\SocialCare\SocialCareIntegrationService::class);
                        try {
                            $subOrder = $integrationService->createSubOrderFromSocialCare(
                                $record->order,
                                \App\Enums\ServiceType::ECO_DISPOSAL,
                                ['description' => $data['description']],
                                auth()->user()
                            );
                            $this->notify('success', 'Эко-заказ создан: #'.$subOrder->order_number);
                        } catch (\Exception $e) {
                            $this->notify('danger', 'Ошибка: '.$e->getMessage());
                        }
                    })
                    ->visible(fn (CareOrderDetails $record) => $record->order && $record->order->isSocialCare()),

                Tables\Actions\Action::make('viewParentOrder')
                    ->label('Родительский заказ')
                    ->icon('heroicon-o-arrow-up')
                    ->url(fn (CareOrderDetails $record) => $record->order->parentOrder
                            ? \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $record->order->parentOrder->id])
                            : null
                    )
                    ->visible(fn (CareOrderDetails $record) => $record->order && $record->order->parentOrder),

                Tables\Actions\Action::make('viewSubOrders')
                    ->label('Подзаказы')
                    ->icon('heroicon-o-arrow-down')
                    ->url(fn (CareOrderDetails $record) => \App\Filament\Resources\SocialCareOrderResource::getUrl('view', ['record' => $record->order_id])
                    )
                    ->visible(fn (CareOrderDetails $record) => $record->order && $record->order->subOrders()->exists()),
            ]),
        ];
    }

    protected function calculateRisk(CareOrderDetails $details): string
    {
        $now = now();
        $start = $details->scheduled_start_at;

        if (! $start) {
            return 'NONE';
        }

        $status = $details->care_status;

        // Нет назначенного помощника, до начала < 2ч
        if (! $details->assigned_helper_id && $start->isBetween($now, $now->copy()->addHours(2))) {
            return 'HIGH';
        }

        // EN_ROUTE, но до начала осталось мало / уже прошло
        if ($status === CareOrderStatus::EN_ROUTE->value && $now->greaterThan($start->copy()->addMinutes(15))) {
            return 'HIGH';
        }

        // IN_PROGRESS слишком долго (например, больше запланированной длительности + 30 мин)
        if ($status === CareOrderStatus::IN_PROGRESS->value) {
            $endExpected = $details->scheduled_end_at ?? $start->copy()->addMinutes(90);
            if ($now->greaterThan($endExpected->copy()->addMinutes(30))) {
                return 'MEDIUM';
            }
        }

        // SCHEDULED без помощника, до начала < 4ч
        if ($status === CareOrderStatus::SCHEDULED->value && ! $details->assigned_helper_id &&
            $start->isBetween($now, $now->copy()->addHours(4))) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    /**
     * Получить статистику Health Tracking
     */
    public function getHealthStats(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        // Активные планы заботы
        $activePlans = \App\Models\CarePlan::where('status', 'ACTIVE')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->count();

        // Клиенты под заботой
        $clientsUnderCare = \App\Models\ClientProfile::whereHas('carePlans', function ($q) {
            $q->where('status', 'ACTIVE')
                ->where('starts_at', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                });
        })->count();

        // Визиты сегодня
        $todayVisits = CareOrderDetails::whereDate('scheduled_start_at', $today)->count();
        $todayCompleted = CareOrderDetails::whereDate('scheduled_start_at', $today)
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->count();

        // Визиты на этой неделе
        $weekVisits = CareOrderDetails::where('scheduled_start_at', '>=', $thisWeek)->count();
        $weekCompleted = CareOrderDetails::where('scheduled_start_at', '>=', $thisWeek)
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->count();

        // Средняя оценка качества (из visit reports - используем статус как индикатор)
        $completedReports = \App\Models\VisitReport::where('created_at', '>=', $thisMonth)
            ->where('status', 'COMPLETED')
            ->count();
        $totalReports = \App\Models\VisitReport::where('created_at', '>=', $thisMonth)->count();
        $avgRating = $totalReports > 0 ? round(($completedReports / $totalReports) * 5, 1) : 0;

        // Активные помощники
        $activeHelpers = SocialHelperProfile::where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->count();

        return [
            'active_plans' => $activePlans,
            'clients_under_care' => $clientsUnderCare,
            'today_visits' => $todayVisits,
            'today_completed' => $todayCompleted,
            'week_visits' => $weekVisits,
            'week_completed' => $weekCompleted,
            'completion_rate' => $weekVisits > 0 ? round(($weekCompleted / $weekVisits) * 100, 1) : 0,
            'avg_rating' => round($avgRating, 1),
            'active_helpers' => $activeHelpers,
        ];
    }
}

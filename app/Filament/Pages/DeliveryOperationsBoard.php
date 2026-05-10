<?php

namespace App\Filament\Pages;

use App\Enums\DeliveryTrackingStatus;
use App\Models\Delivery\DeliveryOrder;
use App\Services\Delivery\CourierSelectorService;
use App\Services\Delivery\GeofenceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DeliveryOperationsBoard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Операционный центр доставки';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?int $navigationSort = 101;

    protected static ?string $slug = 'delivery-operations';

    protected static ?string $title = 'Операционный центр доставки';

    protected static string $view = 'filament.pages.delivery-operations-board';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('viewAny', DeliveryOrder::class);
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn (DeliveryOrder $record) => $record->type?->value ?? 'unknown')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'grocery' => 'Продукты',
                        'bulky' => 'Крупногабарит',
                        'food' => 'Еда',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'grocery' => 'success',
                        'bulky' => 'warning',
                        'food' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tracking_status')
                    ->label('Статус')
                    ->badge()
                    ->getStateUsing(fn (DeliveryOrder $record) => $record->tracking_status?->value ?? 'pending')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'Ожидает',
                        'assigned' => 'Назначен',
                        'picked_up' => 'Забран',
                        'in_transit' => 'В пути',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'secondary',
                        'assigned' => 'warning',
                        'picked_up' => 'info',
                        'in_transit' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('courier.name')
                    ->label('Курьер')
                    ->sortable()
                    ->default('— не назначен —')
                    ->placeholder('— не назначен —')
                    ->searchable()
                    ->icon(fn ($record) => $record->courier_id ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->iconPosition('before')
                    ->color(fn ($record) => $record->courier_id ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('Срочно')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation'),
                Tables\Columns\TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime('H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->default('—'),
                Tables\Columns\TextColumn::make('estimated_duration_minutes')
                    ->label('Мин.')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0).' мин' : '—')
                    ->default('—'),
                Tables\Columns\TextColumn::make('estimated_distance_km')
                    ->label('Км')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1, ',', ' ').' км' : '—')
                    ->default('—'),
                Tables\Columns\TextColumn::make('delivery_address')
                    ->label('Адрес доставки')
                    ->limit(40)
                    ->wrap()
                    ->searchable()
                    ->placeholder('—')
                    ->default('—')
                    ->copyable()
                    ->copyMessage('Адрес скопирован!'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m H:i')
                    ->sortable()
                    ->default('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип доставки')
                    ->options([
                        'grocery' => 'Продукты',
                        'bulky' => 'Крупногабарит',
                        'food' => 'Еда',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('tracking_status')
                    ->label('Статус')
                    ->options([
                        DeliveryTrackingStatus::PENDING->value => 'Ожидает',
                        DeliveryTrackingStatus::ASSIGNED->value => 'Назначен',
                        DeliveryTrackingStatus::PICKED_UP->value => 'Забран',
                        DeliveryTrackingStatus::IN_TRANSIT->value => 'В пути',
                        DeliveryTrackingStatus::DELIVERED->value => 'Доставлен',
                        DeliveryTrackingStatus::CANCELLED->value => 'Отменён',
                    ])
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_urgent')
                    ->label('Срочный заказ'),
                Tables\Filters\TernaryFilter::make('courier_id')
                    ->label('Назначен курьер')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('courier_id'),
                        false: fn (Builder $query) => $query->whereNull('courier_id'),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->searchPlaceholder('Поиск по адресу, курьеру, ID...')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Нет активных заказов')
            ->emptyStateDescription('Все заказы доставки обработаны или отсутствуют активные заказы.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->deferLoading(false)
            ->actions([
                Tables\Actions\Action::make('toggleUrgent')
                    ->label('Сделать срочным/обычным')
                    ->icon('heroicon-o-exclamation')
                    ->visible(fn (DeliveryOrder $record) => Auth::user()?->can('update', $record) ?? false)
                    ->action(function (DeliveryOrder $record) {
                        $record->is_urgent = ! $record->is_urgent;
                        $record->save();

                        Notification::make()
                            ->title($record->is_urgent ? 'Заказ помечен как срочный' : 'Срочность снята')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('recalculateEta')
                    ->label('Пересчитать ETA')
                    ->icon('heroicon-o-clock')
                    ->visible(fn (DeliveryOrder $record) => Auth::user()?->can('update', $record) ?? false)
                    ->action(function (DeliveryOrder $record, GeofenceService $geofenceService) {
                        if (! $record->pickup_location || ! $record->delivery_location) {
                            Notification::make()
                                ->title('Нельзя пересчитать ETA — нет координат')
                                ->warning()
                                ->send();

                            return;
                        }

                        $route = $geofenceService->buildRouteEstimate(
                            $record->pickup_location,
                            $record->delivery_location,
                            $record->type?->value ?? 'grocery',
                        );

                        $record->estimated_distance_km = $route['distance_km'] ?? $record->estimated_distance_km;
                        $record->estimated_duration_minutes = $route['duration_minutes'] ?? $record->estimated_duration_minutes;
                        $record->eta = $route['eta'] ?? $record->eta;
                        $record->save();

                        Notification::make()
                            ->title('ETA успешно пересчитан')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('autoAssignCourier')
                    ->label('Авто-назначить курьера')
                    ->icon('heroicon-o-sparkles')
                    ->visible(fn (DeliveryOrder $record) => ($record->courier_id === null) && (Auth::user()?->can('update', $record) ?? false))
                    ->requiresConfirmation()
                    ->action(function (DeliveryOrder $record, CourierSelectorService $selector) {
                        $courier = $selector->findForDelivery($record);

                        if (! $courier) {
                            Notification::make()
                                ->title('Нет доступных курьеров для этого заказа')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->courier_id = $courier->id;
                        if ($record->tracking_status->value === DeliveryTrackingStatus::PENDING->value) {
                            $record->tracking_status = DeliveryTrackingStatus::ASSIGNED;
                        }
                        $record->save();

                        Notification::make()
                            ->title("Курьер {$courier->name} назначен")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markUrgent')
                    ->label('Пометить выбранные как срочные')
                    ->icon('heroicon-o-exclamation')
                    ->authorize(fn () => Auth::user()?->can('viewAny', DeliveryOrder::class) ?? false)
                    ->action(function ($records) {
                        /** @var \Illuminate\Support\Collection $records */
                        $records->each(function (Model $record) {
                            $record->is_urgent = true;
                            $record->save();
                        });

                        Notification::make()
                            ->title('Выбранные заказы помечены как срочные')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return DeliveryOrder::query()
            ->with(['courier:id,name,phone', 'order:id,order_number,total_amount'])
            ->whereIn('tracking_status', [
                DeliveryTrackingStatus::PENDING->value,
                DeliveryTrackingStatus::ASSIGNED->value,
                DeliveryTrackingStatus::PICKED_UP->value,
                DeliveryTrackingStatus::IN_TRANSIT->value,
            ]);
    }

    public function getStats(): array
    {
        $query = $this->getTableQuery();

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('tracking_status', DeliveryTrackingStatus::PENDING->value)->count(),
            'assigned' => (clone $query)->where('tracking_status', DeliveryTrackingStatus::ASSIGNED->value)->count(),
            'in_transit' => (clone $query)->where('tracking_status', DeliveryTrackingStatus::IN_TRANSIT->value)->count(),
            'urgent' => (clone $query)->where('is_urgent', true)->count(),
            'without_courier' => (clone $query)->whereNull('courier_id')->count(),
        ];
    }
}

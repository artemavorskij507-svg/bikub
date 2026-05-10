<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RoadsideDashboardWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    protected function getTableQuery(): Builder
    {
        try {
            return Order::query()
                ->whereHas('roadsideDetails')
                ->whereIn('status', ['pending', 'assigned', 'in_progress', 'confirmed'])
                ->with([
                    'roadsideDetails.partner',
                    'assignedUser',
                    'orderItems.serviceType',
                ])
                ->orderBy('created_at', 'desc');
        } catch (\Exception $e) {
            // Return empty query on error
            return Order::query()->whereRaw('1 = 0');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID заказа')
                    ->formatStateUsing(fn ($record) => $record->order_number ?? "#{$record->id}")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('roadsideDetails.incident_address')
                    ->label('Адрес инцидента')
                    ->searchable()
                    ->limit(40)
                    ->default('—')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('vehicle_info')
                    ->label('Автомобиль')
                    ->formatStateUsing(function ($record) {
                        if (! $record->roadsideDetails) {
                            return '—';
                        }
                        $parts = array_filter([
                            $record->roadsideDetails->vehicle_make,
                            $record->roadsideDetails->vehicle_model,
                            $record->roadsideDetails->vehicle_plate,
                        ]);

                        return $parts ? implode(' ', $parts) : '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('roadsideDetails', function ($q) use ($search) {
                            $q->where('vehicle_make', 'like', "%{$search}%")
                                ->orWhere('vehicle_model', 'like', "%{$search}%")
                                ->orWhere('vehicle_plate', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'assigned',
                        'info' => 'in_progress',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ожидает',
                        'assigned' => 'Назначен',
                        'in_progress' => 'В работе',
                        'confirmed' => 'Подтвержден',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Исполнитель')
                    ->default('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roadsideDetails.partner.name')
                    ->label('Партнёр-эвакуатор')
                    ->default('—')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Открыть заказ')
                    ->icon('heroicon-o-external-link')
                    ->url(fn (Order $record): string => \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $record])
                    )
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->heading('Активные заказы Roadside & Tow');
    }
}

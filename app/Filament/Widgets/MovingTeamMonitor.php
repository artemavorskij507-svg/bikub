<?php

namespace App\Filament\Widgets;

use App\Models\Moving\Team;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MovingTeamMonitor extends BaseWidget
{
    protected static ?string $heading = 'Бригади переїздів';

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Team::query()
            ->withCount([
                'executors',
                'movingOrders as active_orders_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed', 'in_progress']),
            ]);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Бригада')
                ->searchable(),
            BadgeColumn::make('status')
                ->label('Статус')
                ->colors([
                    'success' => 'active',
                    'warning' => 'inactive',
                    'danger' => 'suspended',
                ]),
            TextColumn::make('executors_count')
                ->label('Учасників')
                ->sortable(),
            TextColumn::make('active_orders_count')
                ->label('Активні замовлення')
                ->sortable(),
            TextColumn::make('max_orders')
                ->label('Макс. замовлень')
                ->sortable(),
            TextColumn::make('rating')
                ->label('Рейтинг')
                ->sortable(),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\TravelTime;
use Carbon\Carbon;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class TravelTimesTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Travel Times Table';
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $now = Carbon::now();

        return TravelTime::query()
            ->where('measured_at', '>=', $now->copy()->subHours(2))
            ->orderBy('status', 'asc')
            ->orderBy('measured_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('route_name')
                    ->label('Маршрут')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('from_location')
                    ->label('Откуда')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('to_location')
                    ->label('Куда')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('travel_time_seconds')
                    ->label('Время')
                    ->formatStateUsing(fn ($record) => $record->travel_time_seconds
                        ? round($record->travel_time_seconds / 60, 1).' мин'
                        : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('distance_meters')
                    ->label('Расстояние')
                    ->formatStateUsing(fn ($record) => $record->distance_meters
                        ? round($record->distance_meters / 1000, 1).' км'
                        : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('average_speed_kmh')
                    ->label('Скорость')
                    ->formatStateUsing(fn ($record) => $record->average_speed_kmh
                        ? round($record->average_speed_kmh, 1).' км/ч'
                        : 'N/A')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'danger' => fn ($state) => $state === 'delayed',
                        'warning' => fn ($state) => $state === 'congested',
                        'success' => fn ($state) => $state === 'normal',
                        'gray' => fn ($state) => ! in_array($state, ['delayed', 'congested', 'normal']),
                    ]),

                Tables\Columns\TextColumn::make('measured_at')
                    ->label('Измерено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('measured_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}

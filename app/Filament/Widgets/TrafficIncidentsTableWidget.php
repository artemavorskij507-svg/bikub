<?php

namespace App\Filament\Widgets;

use App\Models\TrafficIncident;
use Carbon\Carbon;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class TrafficIncidentsTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Traffic Incidents Table';
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $now = Carbon::now();
        $yesterday = $now->copy()->subDay();

        // Show active incidents that haven't ended, or any incidents created in the last 24 hours
        return TrafficIncident::query()
            ->where(function ($query) use ($now) {
                // Active incidents that haven't ended yet
                $query->where('status', 'active')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>', $now);
                    });
            })
            ->orWhere(function ($query) use ($yesterday) {
                // Or any incidents created in the last 24 hours
                $query->where('created_at', '>=', $yesterday);
            })
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Приоритет')
                    ->colors([
                        'danger' => fn ($state) => $state === 'high',
                        'warning' => fn ($state) => $state === 'moderate',
                        'info' => fn ($state) => $state === 'low',
                        'gray' => fn ($state) => ! in_array($state, ['high', 'moderate', 'low']),
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => fn ($state) => $state === 'active',
                        'gray' => fn ($state) => $state === 'resolved',
                        'warning' => fn ($state) => ! in_array($state, ['active', 'resolved']),
                    ]),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Координаты')
                    ->formatStateUsing(fn ($record) => $record->lat && $record->lng
                        ? round($record->lat, 4).', '.round($record->lng, 4)
                        : 'N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}

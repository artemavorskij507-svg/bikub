<?php

namespace App\Filament\Widgets;

use App\Services\SocialCare\SocialCareAnalyticsService;
use Carbon\Carbon;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SocialCareClientsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Покрытие клиентов';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public ?string $periodPreset = '30d';

    public ?string $city = null;

    protected function getTableQuery(): Builder
    {
        // This widget doesn't use a direct query, but gets data from service
        return \App\Models\ClientProfile::query()->whereRaw('1 = 0');
    }

    public function table(Table $table): Table
    {
        $table = $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->actions([])
            ->bulkActions([])
            ->heading(static::$heading)
            ->defaultSort(null);

        // Override recordAction to handle custom records
        $table->recordAction(null);
        $table->recordUrl(null);

        return $table;
    }

    public function getTableRecordKey($record): string
    {
        // Use client_id as key for custom records
        return (string) ($record->client_id ?? $record->id ?? uniqid());
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('client_name')
                ->label('Клиент')
                ->getStateUsing(fn ($record) => $record?->getAttribute('client_name') ?? '—')
                ->searchable(false)
                ->sortable(false),

            Tables\Columns\TextColumn::make('city')
                ->label('Город')
                ->getStateUsing(fn ($record) => $record?->getAttribute('city') ?? '—')
                ->searchable(false)
                ->sortable(false),

            Tables\Columns\TextColumn::make('visits_count')
                ->label('Визитов')
                ->getStateUsing(fn ($record) => $record?->getAttribute('visits_count') ?? 0)
                ->sortable(false)
                ->alignCenter(),

            Tables\Columns\TextColumn::make('total_hours')
                ->label('Часов помощи')
                ->getStateUsing(fn ($record) => number_format($record?->getAttribute('total_hours') ?? 0, 1, ',', ' '))
                ->sortable(false)
                ->alignCenter(),

            Tables\Columns\IconColumn::make('has_active_care_plan')
                ->label('Активный план')
                ->getStateUsing(fn ($record) => $record?->getAttribute('has_active_care_plan') ?? false)
                ->boolean(),

            Tables\Columns\IconColumn::make('has_trusted_contact')
                ->label('Доверенное лицо')
                ->getStateUsing(fn ($record) => $record?->getAttribute('has_trusted_contact') ?? false)
                ->boolean(),
        ];
    }

    protected function getTableActions(): array
    {
        return [];
    }

    protected function getTableBulkActions(): array
    {
        return [];
    }

    public function getTableRecords(): Collection
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $data = $analytics->clientsCoverage($from, $to, $this->city);

        if (! $data || $data->isEmpty()) {
            return new Collection;
        }

        // Create temporary model instances to satisfy Filament's type requirements
        $models = $data->map(function ($item) {
            if (! $item) {
                return null;
            }
            $model = new \App\Models\ClientProfile;
            foreach ((array) $item as $key => $value) {
                $model->setAttribute($key, $value);
            }
            // Set a fake ID to prevent issues
            $model->setAttribute('id', $item->client_id ?? uniqid());
            $model->exists = true;

            return $model;
        })->filter();

        // Convert Support Collection to Eloquent Collection
        return new Collection($models->all());
    }

    protected function resolvePeriod(): array
    {
        $to = now();
        switch ($this->periodPreset) {
            case 'today':
                $from = $to->copy()->startOfDay();
                break;
            case '7d':
                $from = $to->copy()->subDays(7);
                break;
            case 'quarter':
                $from = $to->copy()->startOfQuarter();
                break;
            case 'year':
                $from = $to->copy()->startOfYear();
                break;
            case 'all':
                $from = Carbon::minValue();
                break;
            case '30d':
            default:
                $from = $to->copy()->subDays(30);
                break;
        }

        return [$from, $to];
    }
}

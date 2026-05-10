<?php

namespace App\Filament\Widgets;

use App\Services\SocialCare\SocialCareAnalyticsService;
use Carbon\Carbon;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SocialCareHelpersTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Нагрузка и вклад помощников';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public ?string $periodPreset = '30d';

    public ?string $helperLevel = null;

    protected function getTableQuery(): Builder
    {
        // This widget doesn't use a direct query, but gets data from service
        // Return empty query as placeholder
        return \App\Models\SocialHelperProfile::query()->whereRaw('1 = 0');
    }

    public function getTableRecords(): Collection
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $data = $analytics->helpersLoad($from, $to, $this->helperLevel);

        if (! $data || $data->isEmpty()) {
            return new Collection;
        }

        // Create temporary model instances to satisfy Filament's type requirements
        $models = $data->map(function ($item) {
            if (! $item) {
                return null;
            }
            $model = new \App\Models\SocialHelperProfile;
            foreach ((array) $item as $key => $value) {
                $model->setAttribute($key, $value);
            }
            // Set a fake ID to prevent issues
            $model->setAttribute('id', $item->helper_id ?? uniqid());
            $model->exists = true;

            return $model;
        })->filter();

        // Convert Support Collection to Eloquent Collection
        return new Collection($models->all());
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

        // Override recordAction to handle stdClass objects
        $table->recordAction(null);
        $table->recordUrl(null);

        return $table;
    }

    public function getTableRecordKey($record): string
    {
        // Use helper_id as key for stdClass objects
        return (string) ($record->helper_id ?? uniqid());
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('helper_name')
                ->label('Имя помощника')
                ->getStateUsing(fn ($record) => $record?->getAttribute('helper_name') ?? '—')
                ->searchable(false)
                ->sortable(false),

            Tables\Columns\BadgeColumn::make('level')
                ->label('Уровень')
                ->getStateUsing(fn ($record) => $record?->getAttribute('level') ?? '—')
                ->colors([
                    'primary' => 'SOCIAL_HELPER',
                    'info' => 'COMMUNITY_PARTNER',
                    'success' => 'BIKUBE_FRIEND',
                ])
                ->formatStateUsing(fn ($state) => match ($state) {
                    'SOCIAL_HELPER' => 'Social Helper',
                    'COMMUNITY_PARTNER' => 'Community Partner',
                    'BIKUBE_FRIEND' => 'Bikube Friend',
                    default => $state,
                }),

            Tables\Columns\TextColumn::make('visits_count')
                ->label('Визитов')
                ->getStateUsing(fn ($record) => $record?->getAttribute('visits_count') ?? 0)
                ->sortable(false)
                ->alignCenter(),

            Tables\Columns\TextColumn::make('total_hours')
                ->label('Часов')
                ->getStateUsing(fn ($record) => number_format($record?->getAttribute('total_hours') ?? 0, 1, ',', ' '))
                ->sortable(false)
                ->alignCenter(),

            Tables\Columns\TextColumn::make('volunteer_hours')
                ->label('Волонтёрские часы')
                ->getStateUsing(fn ($record) => number_format($record?->getAttribute('volunteer_hours') ?? 0, 1, ',', ' '))
                ->sortable(false)
                ->alignCenter()
                ->visible(fn ($record) => ($record?->getAttribute('volunteer_hours') ?? 0) > 0),

            Tables\Columns\TextColumn::make('rating_avg')
                ->label('Рейтинг')
                ->getStateUsing(fn ($record) => ($record?->getAttribute('rating_avg')) ? number_format($record->getAttribute('rating_avg'), 1, ',', ' ') : '—')
                ->sortable(false)
                ->alignCenter(),

            Tables\Columns\IconColumn::make('is_active')
                ->label('Активен')
                ->getStateUsing(fn ($record) => $record?->getAttribute('is_active') ?? false)
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

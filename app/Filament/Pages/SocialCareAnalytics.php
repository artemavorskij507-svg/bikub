<?php

namespace App\Filament\Pages;

use App\Support\Local\SocialCareLocalDemoSeeder;
use App\Services\SocialCare\SocialCareAnalyticsService;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SocialCareAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Social Care';

    protected static ?string $navigationLabel = 'Аналитика и отчёты';

    protected static ?string $title = 'Аналитика Social Care';

    protected static ?int $navigationSort = 701;

    protected static string $view = 'filament.pages.social-care-analytics';

    public ?string $periodPreset = '30d';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $helperLevel = null;

    public ?int $careServiceId = null;

    public ?string $city = null;

    public array $kpi = [];

    public array $visitsByDay = [];

    public array $servicesDistribution = [];

    public array $helpersLoad = [];

    public array $clientsCoverage = [];

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

    public function getTitle(): string|Htmlable
    {
        return 'Аналитика Social Care';
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('periodPreset')
                ->label('Период')
                ->options([
                    'today' => 'Сегодня',
                    '7d' => '7 дней',
                    '30d' => '30 дней',
                    'quarter' => 'Квартал',
                    'year' => 'Год',
                    'all' => 'За всё время',
                    'custom' => 'Произвольный период',
                ])
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),

            Forms\Components\DatePicker::make('dateFrom')
                ->label('С даты')
                ->visible(fn ($get) => $get('periodPreset') === 'custom')
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),

            Forms\Components\DatePicker::make('dateTo')
                ->label('По дату')
                ->visible(fn ($get) => $get('periodPreset') === 'custom')
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),

            Forms\Components\Select::make('helperLevel')
                ->label('Уровень помощника')
                ->options([
                    'SOCIAL_HELPER' => 'Social Helper (PRO)',
                    'COMMUNITY_PARTNER' => 'Community Partner',
                    'BIKUBE_FRIEND' => 'Bikube Friend',
                ])
                ->placeholder('Все уровни')
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),

            Forms\Components\Select::make('careServiceId')
                ->label('Тип услуги')
                ->options(function () {
                    return \App\Models\CareService::query()
                        ->where('is_active', true)
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->placeholder('Все услуги')
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),

            Forms\Components\Select::make('city')
                ->label('Город')
                ->options(function () {
                    return \App\Models\ClientProfile::query()
                        ->whereNotNull('city')
                        ->distinct('city')
                        ->pluck('city', 'city');
                })
                ->searchable()
                ->placeholder('Все города')
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),
        ];
    }

    public function mount(SocialCareAnalyticsService $analytics): void
    {
        SocialCareLocalDemoSeeder::run();
        $this->loadData($analytics);
    }

    public function loadData(?SocialCareAnalyticsService $analytics = null): void
    {
        $analytics ??= app(SocialCareAnalyticsService::class);

        [$from, $to] = $this->resolvePeriod();

        $this->kpi = $analytics->aggregateKpi($from, $to, $this->helperLevel, $this->careServiceId, $this->city);
        $this->visitsByDay = $analytics->visitsAndHoursByDay($from, $to, $this->helperLevel, $this->careServiceId, $this->city)->toArray();
        $this->servicesDistribution = $analytics->servicesDistribution($from, $to, $this->helperLevel, $this->city)->toArray();
        $this->helpersLoad = $analytics->helpersLoad($from, $to, $this->helperLevel)->toArray();
        $this->clientsCoverage = $analytics->clientsCoverage($from, $to, $this->city)->toArray();
    }

    protected function resolvePeriod(): array
    {
        $to = now();

        if ($this->periodPreset === 'custom' && $this->dateFrom && $this->dateTo) {
            try {
                return [
                    Carbon::parse($this->dateFrom)->startOfDay(),
                    Carbon::parse($this->dateTo)->endOfDay(),
                ];
            } catch (\Exception $e) {
                \Log::warning('Failed to parse dates in SocialCareAnalytics', [
                    'dateFrom' => $this->dateFrom,
                    'dateTo' => $this->dateTo,
                    'error' => $e->getMessage(),
                ]);

                return [now()->startOfDay(), now()->endOfDay()];
            }
        }

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

    public function exportHelpersCsv(): StreamedResponse
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $helpers = $analytics->helpersLoad($from, $to, $this->helperLevel);

        $callback = function () use ($helpers) {
            $out = fopen('php://output', 'w');

            // Header with confidentiality notice
            fputcsv($out, ['CONFIDENTIAL - Social Care Analytics Export']);
            fputcsv($out, ['Generated: '.now()->toDateTimeString()]);
            fputcsv($out, []);

            fputcsv($out, [
                'helper_id',
                'name',
                'level',
                'visits_count',
                'total_hours',
                'volunteer_hours',
                'rating_avg',
                'rating_count',
                'is_active',
            ]);

            foreach ($helpers as $helper) {
                fputcsv($out, [
                    $helper['helper_id'],
                    $helper['helper_name'],
                    $helper['level'],
                    $helper['visits_count'],
                    number_format($helper['total_hours'], 2, '.', ''),
                    number_format($helper['volunteer_hours'], 2, '.', ''),
                    $helper['rating_avg'] ?? '',
                    $helper['rating_count'] ?? 0,
                    $helper['is_active'] ? '1' : '0',
                ]);
            }

            fclose($out);
        };

        $filename = 'social-care-helpers-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportClientsCsv(): StreamedResponse
    {
        $analytics = app(SocialCareAnalyticsService::class);
        [$from, $to] = $this->resolvePeriod();

        $clients = $analytics->clientsCoverage($from, $to, $this->city);

        $callback = function () use ($clients) {
            $out = fopen('php://output', 'w');

            // Header with confidentiality notice
            fputcsv($out, ['CONFIDENTIAL - Social Care Analytics Export']);
            fputcsv($out, ['Generated: '.now()->toDateTimeString()]);
            fputcsv($out, []);

            fputcsv($out, [
                'client_id',
                'full_name',
                'city',
                'visits_count',
                'total_hours',
                'has_active_care_plan',
                'has_trusted_contact',
            ]);

            foreach ($clients as $client) {
                fputcsv($out, [
                    $client['client_id'],
                    $client['client_name'],
                    $client['city'],
                    $client['visits_count'],
                    number_format($client['total_hours'], 2, '.', ''),
                    $client['has_active_care_plan'] ? '1' : '0',
                    $client['has_trusted_contact'] ? '1' : '0',
                ]);
            }

            fclose($out);
        };

        $filename = 'social-care-clients-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\SocialCareKpiWidget::class,
            \App\Filament\Widgets\SocialCareVisitsChartWidget::class,
            \App\Filament\Widgets\SocialCareServicesChartWidget::class,
            \App\Filament\Widgets\SocialCareHelpersTableWidget::class,
            \App\Filament\Widgets\SocialCareClientsTableWidget::class,
        ];
    }

    protected function getActions(): array
    {
        return [
            \Filament\Pages\Actions\Action::make('exportHelpersCsv')
                ->label('Экспорт помощников (CSV)')
                ->icon('heroicon-o-download')
                ->action('exportHelpersCsv'),

            \Filament\Pages\Actions\Action::make('exportClientsCsv')
                ->label('Экспорт клиентов (CSV)')
                ->icon('heroicon-o-download')
                ->action('exportClientsCsv'),
        ];
    }
}

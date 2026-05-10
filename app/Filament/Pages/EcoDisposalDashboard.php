<?php

namespace App\Filament\Pages;

use App\Services\EcoDisposal\EcoDisposalAnalyticsService;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EcoDisposalDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Eco Disposal';

    protected static ?string $navigationLabel = 'Аналитика ЭКО-услуг';

    protected static ?int $navigationSort = 305;

    protected static string $view = 'filament.pages.eco-disposal-dashboard';

    public ?string $periodPreset = '30d';

    public array $summary = [];

    public array $timeSeries = [];

    public array $topPartners = [];

    public array $categoryBreakdown = [];

    public array $zoneBreakdown = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // TODO: при необходимости ограничить ролями (admin/dispatcher/analyst).
        return true;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Аналитика ЭКО-услуг';
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('periodPreset')
                ->label('Период')
                ->options([
                    '7d' => '7 дней',
                    '30d' => '30 дней',
                    '90d' => '90 дней',
                    'all' => 'За всё время',
                ])
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadData()),
        ];
    }

    public function mount(EcoDisposalAnalyticsService $analytics): void
    {
        $this->ensureEcoDisposalSchema();
        $this->loadData($analytics);
    }

    public function loadData(?EcoDisposalAnalyticsService $analytics = null): void
    {
        $analytics ??= app(EcoDisposalAnalyticsService::class);

        [$from, $to] = $this->resolvePeriod();

        $this->summary = $analytics->getSummary($from, $to);
        $this->timeSeries = $analytics->getTimeSeries($from, $to)->toArray();
        $this->topPartners = $analytics->getTopPartners($from, $to)->toArray();
        $this->categoryBreakdown = $analytics->getCategoryBreakdown($from, $to)->toArray();
        $this->zoneBreakdown = $analytics->getZoneBreakdown($from, $to)->toArray();
    }

    protected function resolvePeriod(): array
    {
        $to = now();

        switch ($this->periodPreset) {
            case '7d':
                $from = $to->copy()->subDays(7);
                break;
            case '90d':
                $from = $to->copy()->subDays(90);
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

    public function exportCsv()
    {
        [$from, $to] = $this->resolvePeriod();

        $orders = \App\Models\Order::query()
            ->where('metadata->service_type', 'eco_disposal')
            ->whereBetween('created_at', [$from, $to])
            ->with(['disposalDetails', 'ecoCertificate'])
            ->get();

        $callback = function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'order_id',
                'order_number',
                'created_at',
                'completed_at',
                'zone_code',
                'partner_name',
                'total_volume_m3',
                'total_weight_kg',
                'co2_saved_kg',
                'items_reused_count',
            ]);

            foreach ($orders as $order) {
                $zoneCode = $order->metadata['zone_code'] ?? null;
                $partnerName = $order->disposalDetails?->ecoPartner?->name ?? null;
                $volume = $order->disposalDetails?->estimated_volume_m3 ?? null;
                $weight = $order->disposalDetails?->estimated_weight_kg ?? null;
                $co2 = $order->ecoCertificate?->co2_saved_kg ?? null;
                $reused = $order->ecoCertificate?->items_reused_count ?? null;

                fputcsv($out, [
                    $order->id,
                    $order->order_number,
                    optional($order->created_at)->toDateTimeString(),
                    optional($order->completed_at)->toDateTimeString(),
                    $zoneCode,
                    $partnerName,
                    $volume,
                    $weight,
                    $co2,
                    $reused,
                ]);
            }

            fclose($out);
        };

        $filename = 'eco-disposal-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getActions(): array
    {
        return [
            \Filament\Pages\Actions\Action::make('exportCsv')
                ->label('Экспорт в CSV')
                ->icon('heroicon-o-download')
                ->action('exportCsv'),
        ];
    }

    protected function ensureEcoDisposalSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $paths = [
            'database/migrations/2025_11_16_120300_create_disposal_order_details_table.php',
            'database/migrations/2025_11_16_130500_add_dispatch_fields_to_disposal_order_details_table.php',
            'database/migrations/2025_11_16_120100_create_disposal_partners_table.php',
            'database/migrations/2025_11_16_120000_create_disposal_items_table.php',
            'database/migrations/2025_11_16_120040_create_eco_certificates_table.php',
            'database/migrations/2025_11_16_120400_create_eco_certificates_table.php',
            'database/migrations/2025_11_16_120200_create_eco_teams_table.php',
        ];

        foreach ($paths as $path) {
            if (! is_file(base_path($path))) {
                continue;
            }

            try {
                Artisan::call('migrate', [
                    '--path' => $path,
                    '--force' => true,
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Eco disposal dashboard migration bootstrap failed', [
                    'path' => $path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}


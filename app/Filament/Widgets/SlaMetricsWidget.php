<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\SlaService;
use Filament\Widgets\Widget;

class SlaMetricsWidget extends Widget
{
    protected static string $view = 'filament.widgets.sla-metrics';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 40;

    public bool $isCollapsed = false;

    public function toggleCollapse()
    {
        $this->isCollapsed = ! $this->isCollapsed;
        $this->emit('refreshComponent');
    }

    protected function getViewData(): array
    {
        $orders = Order::query()
            ->select(['id', 'sla_deadline', 'completed_at', 'weather_conditions', 'created_at'])
            ->where('created_at', '>=', now()->subDays(30))
            ->limit(1000)
            ->get();

        $slaService = app(SlaService::class);
        $atRiskOrders = $slaService->getOrdersAtRisk();

        return [
            'totalOrders' => $orders->count(),
            'breachedOrders' => $orders->where('sla_deadline', '<', now())->count(),
            'atRiskOrders' => count($atRiskOrders),
            'breachRate' => $orders->count() > 0 ? round($orders->where('sla_deadline', '<', now())->count() / $orders->count() * 100, 1) : 0,
            'averageBreachTime' => $this->calculateAverageBreachTime($orders),
            'weatherImpact' => $this->getWeatherImpact($orders),
            'isCollapsed' => $this->isCollapsed,
        ];
    }

    private function calculateAverageBreachTime($orders): float
    {
        $breachedOrders = $orders
            ->filter(fn ($order) => $order->sla_deadline && $order->sla_deadline < now());

        if ($breachedOrders->isEmpty()) {
            return 0;
        }

        $totalBreachTime = 0;
        foreach ($breachedOrders as $order) {
            $totalBreachTime += $order->sla_deadline->diffInMinutes($order->completed_at ?? now());
        }

        return round($totalBreachTime / $breachedOrders->count(), 1);
    }

    private function getWeatherImpact($orders): array
    {
        $weatherImpact = [
            'clear' => ['orders' => 0, 'breaches' => 0],
            'rain' => ['orders' => 0, 'breaches' => 0],
            'snow' => ['orders' => 0, 'breaches' => 0],
            'fog' => ['orders' => 0, 'breaches' => 0],
        ];

        foreach ($orders as $order) {
            if ($order->weather_conditions) {
                $condition = $order->weather_conditions['condition'] ?? 'clear';
                if (! isset($weatherImpact[$condition])) {
                    $condition = 'clear';
                }
                $weatherImpact[$condition]['orders']++;

                if ($order->sla_deadline && $order->sla_deadline < now()) {
                    $weatherImpact[$condition]['breaches']++;
                }
            }
        }

        return $weatherImpact;
    }
}

<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Выручка по дням';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30days';

    protected static ?string $pollingInterval = '30s';

    protected static ?string $maxHeight = '300px';

    protected function getFilters(): ?array
    {
        return [
            '7days' => '7 дней',
            '30days' => '30 дней',
            '90days' => '90 дней',
            'year' => 'Год',
        ];
    }

    protected function getData(): array
    {
        $days = match ($this->filter) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);

        $orders = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];

        $current = $startDate->copy();
        while ($current <= Carbon::now()) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('d.m');

            $order = $orders->firstWhere('date', $dateStr);
            $data[] = $order ? (float) $order->revenue : 0;

            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Выручка (NOK)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class OrdersByStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Заказы по статусам';

    protected static ?int $sort = 30;

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

        $orders = Order::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $statusLabels = [
            'pending' => 'Ожидает',
            'confirmed' => 'Подтвержден',
            'in_progress' => 'В работе',
            'completed' => 'Завершен',
            'cancelled' => 'Отменен',
        ];

        $labels = [];
        $data = [];
        $colors = [
            'pending' => 'rgb(251, 191, 36)',
            'confirmed' => 'rgb(59, 130, 246)',
            'in_progress' => 'rgb(139, 92, 246)',
            'completed' => 'rgb(34, 197, 94)',
            'cancelled' => 'rgb(239, 68, 68)',
        ];

        $backgroundColors = [];

        foreach ($orders as $order) {
            $labels[] = $statusLabels[$order->status] ?? $order->status;
            $data[] = (int) $order->count;
            $backgroundColors[] = $colors[$order->status] ?? 'rgb(156, 163, 175)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

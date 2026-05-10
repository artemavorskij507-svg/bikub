<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class RoadsideSourceBreakdown extends Widget
{
    protected static string $view = 'filament.widgets.roadside-source-breakdown';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Источники roadside-заказов (30 дней)';

    public function getViewData(): array
    {
        try {
            $thirtyDaysAgo = Carbon::now()->subDays(30);

            $roadsideOrders = Order::query()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->where(function ($q) {
                    $q->whereHas('roadsideDetails')
                        ->orWhereHas('roadsideEmergency')
                        ->orWhereHas('vehicleInspection')
                        ->orWhereHas('orderItems.serviceType', function ($sq) {
                            $sq->where(function ($q) {
                                $q->whereIn('code', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport'])
                                    ->orWhereIn('category', ['roadside_assistance', 'vehicle_inspection', 'vehicle_transport']);
                            });
                        });
                })
                ->with(['parentOrder.orderItems.serviceType', 'orderItems.serviceType'])
                ->get();

            $breakdown = [
                'Прямые' => 0,
                'Переезд' => 0,
                'Эко' => 0,
                'Мастер' => 0,
                'Поручения' => 0,
            ];

            foreach ($roadsideOrders as $order) {
                if ($order->parent_order_id && $order->parentOrder) {
                    $parent = $order->parentOrder;
                    $serviceType = $parent->orderItems->first()?->serviceType;

                    if ($serviceType) {
                        $code = $serviceType->code ?? '';
                        $category = $serviceType->category ?? '';

                        if (str_contains($code, 'relocation') || str_contains($category, 'relocation') || str_contains($code, 'moving')) {
                            $breakdown['Переезд']++;
                        } elseif (str_contains($code, 'eco') || str_contains($code, 'disposal')) {
                            $breakdown['Эко']++;
                        } elseif (str_contains($code, 'handyman') || str_contains($code, 'master')) {
                            $breakdown['Мастер']++;
                        } elseif (str_contains($code, 'errand') || str_contains($code, 'custom')) {
                            $breakdown['Поручения']++;
                        } else {
                            $breakdown['Прямые']++;
                        }
                    } else {
                        $breakdown['Прямые']++;
                    }
                } else {
                    $breakdown['Прямые']++;
                }
            }

            return [
                'breakdown' => $breakdown,
                'total' => array_sum($breakdown),
            ];
        } catch (\Exception $e) {
            // Return empty breakdown on error
            return [
                'breakdown' => [
                    'Прямые' => 0,
                    'Переезд' => 0,
                    'Эко' => 0,
                    'Мастер' => 0,
                    'Поручения' => 0,
                ],
                'total' => 0,
            ];
        }
    }
}

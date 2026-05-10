<?php

namespace App\Services\Orders;

class OrderPricingCoordinator
{
    public function estimate(array $scenario, array $payload): array
    {
        $base = (float) ($scenario['base_price'] ?? 99);
        $currency = (string) ($scenario['currency'] ?? config('order_scenarios.default_currency', 'NOK'));
        $pricingModel = (string) ($scenario['pricing_model'] ?? 'fixed');

        $itemsTotal = collect($payload['items'] ?? [])
            ->sum(static fn ($item): float => (float) ($item['price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1)));

        $total = $base;
        if (in_array($pricingModel, ['base_plus_items', 'estimate'], true)) {
            $total += $itemsTotal;
        }

        return [
            'base_amount' => $base,
            'items_amount' => $itemsTotal,
            'total_amount' => round($total, 2),
            'currency' => $currency,
            'estimated_minutes' => (int) ($scenario['sla_minutes'] ?? 90),
            'pricing_model' => $pricingModel,
        ];
    }
}

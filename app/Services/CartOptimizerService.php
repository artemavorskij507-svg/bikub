<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CartOptimizerService
{
    /**
     * @param  array<int, array{product_id:int, quantity:int}>  $items
     */
    public function optimize(array $items, ?int $zoneId = null, ?int $storeId = null): array
    {
        $shoppingList = collect($items)
            ->map(function (array $item) {
                if (empty($item['product_id']) || empty($item['quantity'])) {
                    throw new InvalidArgumentException('Invalid cart item payload.');
                }

                return [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ];
            })
            ->filter(fn (array $item) => $item['quantity'] > 0)
            ->values();

        if ($shoppingList->isEmpty()) {
            throw new InvalidArgumentException('Cart is empty.');
        }

        $productIds = $shoppingList->pluck('product_id')->unique()->values();

        $pricesQuery = DB::table('product_store_prices as psp')
            ->join('stores', 'psp.store_id', '=', 'stores.id')
            ->whereIn('psp.product_id', $productIds)
            ->where('stores.is_active', true)
            ->select(
                'psp.product_id',
                'psp.store_id',
                'psp.price',
                'stores.name as store_name',
                'stores.zone_id'
            );

        if ($zoneId) {
            $pricesQuery->where('stores.zone_id', $zoneId);
        }

        if ($storeId) {
            $pricesQuery->where('psp.store_id', $storeId);
        }

        $rows = $pricesQuery->get();

        if ($rows->isEmpty()) {
            return [
                'options' => [],
                'summary' => [
                    'total_options' => 0,
                    'best_price' => null,
                ],
            ];
        }

        $byStore = $rows->groupBy('store_id');

        $options = [];

        foreach ($byStore as $storeId => $storeRows) {
            $storeName = $storeRows->first()->store_name;
            $pricesByProduct = $storeRows->keyBy('product_id');

            $allAvailable = $productIds->every(
                fn (int $pid) => $pricesByProduct->has($pid)
            );

            if (! $allAvailable) {
                continue;
            }

            $totalEstimated = $shoppingList->reduce(
                function (int $total, array $item) use ($pricesByProduct): int {
                    $price = $pricesByProduct[$item['product_id']]->price;

                    return $total + ($price * $item['quantity']);
                },
                0
            );

            $options[] = [
                'store_id' => (int) $storeId,
                'store_name' => $storeName,
                'total_estimated' => $totalEstimated,
                'missing_items_count' => 0,
            ];
        }

        $sorted = collect($options)
            ->sortBy('total_estimated')
            ->values()
            ->all();

        return [
            'options' => $sorted,
            'summary' => [
                'total_options' => count($sorted),
                'best_price' => $sorted[0]['total_estimated'] ?? null,
                'requested_store_id' => $storeId,
            ],
        ];
    }
}

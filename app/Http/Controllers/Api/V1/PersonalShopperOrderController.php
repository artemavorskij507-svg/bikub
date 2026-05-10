<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentFlow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StorePersonalShopperOrderRequest;
use App\Models\Order;
use App\Models\ProductStorePrice;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonalShopperOrderController extends Controller
{
    public function store(StorePersonalShopperOrderRequest $request): RedirectResponse
    {
        $user = $request->user();
        $storeId = $request->validated('store_id');
        $products = collect($request->validated('products'));

        $store = Store::query()
            ->where('is_active', true)
            ->findOrFail($storeId);

        $productIds = $products
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        $prices = ProductStorePrice::query()
            ->with('product')
            ->where('store_id', $store->id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        if ($prices->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'products' => 'Выбранный магазин не продаёт один или несколько товаров из списка.',
            ]);
        }

        $cartLines = [];
        $estimatedTotal = 0;

        foreach ($products as $item) {
            $productId = (int) $item['id'];
            $quantity = max(1, (int) $item['quantity']);
            $priceRow = $prices->get($productId);

            if (! $priceRow) {
                throw ValidationException::withMessages([
                    'products' => 'Некоторые товары недоступны в выбранном магазине.',
                ]);
            }

            $lineTotal = $priceRow->price * $quantity;
            $estimatedTotal += $lineTotal;

            $cartLines[] = [
                'product_id' => $priceRow->product->id,
                'product_slug' => $priceRow->product->slug,
                'name' => $priceRow->product->name,
                'quantity' => $quantity,
                'unit_price_cents' => $priceRow->price,
                'line_total_cents' => $lineTotal,
            ];
        }

        if ($estimatedTotal <= 0) {
            throw ValidationException::withMessages([
                'products' => 'Не удалось рассчитать сумму заказа.',
            ]);
        }

        $bufferTotal = (int) round($estimatedTotal * 1.2);

        $order = DB::transaction(function () use ($user, $store, $estimatedTotal, $bufferTotal, $cartLines) {
            return Order::create([
                'user_id' => $user->id,
                'store_id' => $store->id,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'payment_flow' => PaymentFlow::AuthorizeCapture,
                'currency' => 'NOK',
                'estimated_total' => $estimatedTotal,
                'buffer_total' => $bufferTotal,
                'actual_total' => null,
                'total_amount' => $estimatedTotal / 100,
                'metadata' => [
                    'channel' => 'web_personal_shopper',
                    'cart_items' => $cartLines,
                    'zone_id' => session('current_zone_id') ?? session('zone_id'),
                ],
            ]);
        });

        return redirect()
            ->route('public.cart.index')
            ->with('status', sprintf('Заказ %s создан. Мы свяжемся с вами для подтверждения.', $order->order_number));
    }
}

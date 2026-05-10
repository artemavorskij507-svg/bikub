<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\OptimizeCartRequest;
use App\Services\CartOptimizerService;
use Illuminate\View\View;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(protected CartOptimizerService $optimizer) {}

    public function index(): View
    {
        return view('pages.public.cart.index');
    }

    public function optimize(OptimizeCartRequest $request): View
    {
        $items = $request->validated('items');
        $storeId = $request->validated('store_id');
        $zoneId = session('current_zone_id') ?? session('zone_id');

        try {
            $result = $this->optimizer->optimize($items, $zoneId ? (int) $zoneId : null, $storeId ? (int) $storeId : null);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['cart' => $exception->getMessage()])
                ->withInput();
        }

        return view('pages.public.cart.optimize', [
            'result' => $result,
            'items' => $items,
            'storeId' => $storeId,
            'storeName' => $storeId ? optional(\App\Models\Store::find($storeId))->name : null,
        ]);
    }
}

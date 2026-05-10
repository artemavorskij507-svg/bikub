<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Store;

class StoreController extends Controller
{
    public function show(Store $store)
    {
        abort_unless($store->is_active, 404);

        $store->load([
            'zone',
            'products' => function ($query) {
                $query->where('is_active', true)
                    ->withPivot('price')
                    ->orderBy('name');
            },
        ]);

        return view('pages.public.stores.show', [
            'store' => $store,
        ]);
    }
}

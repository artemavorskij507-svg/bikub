<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load([
            'stores' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('order_column')
                    ->withPivot('price');
            },
        ]);

        return view('pages.public.products.show', compact('product'));
    }
}

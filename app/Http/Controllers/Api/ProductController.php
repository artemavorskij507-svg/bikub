<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStorePrice;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get products for marketplace (grocery).
     */
    public function index(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string',
            'store' => 'nullable|string',
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Product::where('is_active', true);

        // Filter by category (if we have categories in metadata or separate table)
        if ($request->category) {
            // This could be enhanced with a proper category relationship
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->category.'%')
                    ->orWhere('description', 'like', '%'.$request->category.'%');
            });
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%')
                    ->orWhere('canonical_name', 'like', '%'.$request->search.'%');
            });
        }

        $limit = $request->limit ?? 50;
        $products = $query->with('storePrices')->orderBy('name')->limit($limit)->get();

        // Get prices from stores efficiently using eager-loaded data
        $productsWithPrices = $products->map(function ($product) use ($request) {
            $price = null;

            if ($request->store) {
                // Use eager-loaded storePrices relationship
                $storePrice = $product->storePrices->first(
                    fn ($p) => $p->store_id === (int) $request->store
                );

                if ($storePrice) {
                    $price = $storePrice->price;
                }
            }

            // If no store price found, try to get first available price from eager-loaded collection
            if ($price === null) {
                $storePrice = $product->storePrices->first();
                // Price might be stored in cents, convert to NOK
                $price = $storePrice ? ($storePrice->price / 100) : 0;
            } else {
                // Convert from cents to NOK if needed
                if ($price > 1000) {
                    $price = $price / 100;
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'image_url' => $product->image_url,
                'price' => $price,
                'base_price' => $price,
                'weight_kg' => $product->weight_kg,
                'volume_m3' => $product->volume_m3,
                'unit' => $product->unit ?? 'шт',
                'sku' => $product->sku,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $productsWithPrices,
            'count' => $productsWithPrices->count(),
        ]);
    }

    /**
     * Get products for cargo/bulky items.
     */
    public function cargo(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        // For cargo, we might want to filter by weight or volume
        $query = Product::where('is_active', true)
            ->where(function ($q) {
                // Heavy items (weight > 5kg or volume > 0.1 m³)
                $q->where('weight_kg', '>', 5)
                    ->orWhere('volume_m3', '>', 0.1);
            });

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $limit = $request->limit ?? 50;
        $products = $query->orderBy('weight_kg', 'desc')->limit($limit)->get();

        $productsWithPrices = $products->map(function ($product) {
            $storePrice = ProductStorePrice::where('product_id', $product->id)
                ->orderBy('price')
                ->first();

            $price = 0;
            if ($storePrice) {
                $price = $storePrice->price;
                // Convert from cents to NOK if needed
                if ($price > 1000) {
                    $price = $price / 100;
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'image_url' => $product->image_url,
                'price' => $price,
                'base_price' => $price,
                'weight_kg' => $product->weight_kg,
                'volume_m3' => $product->volume_m3,
                'dimensions' => $product->dimensions,
                'unit' => $product->unit ?? 'шт',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $productsWithPrices,
            'count' => $productsWithPrices->count(),
        ]);
    }
}

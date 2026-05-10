<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\RetailStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * List delivery stores with optional filtering by type and city.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $city = $request->query('city');

        $query = RetailStore::query()
            ->active()
            ->orderBy('brand')
            ->orderBy('name');

        if ($city) {
            $query->where('city', $city);
        }

        if ($type === 'grocery') {
            $query->where('supports_grocery_delivery', true);
        } elseif ($type === 'bulky') {
            $query->where('supports_bulky_delivery', true);
        }

        $stores = $query->get([
            'id',
            'name',
            'brand',
            'slug',
            'category',
            'address',
            'city',
            'postcode',
            'country',
            'latitude',
            'longitude',
            'phone',
            'supports_grocery_delivery',
            'supports_bulky_delivery',
            'delivery_metadata',
        ]);

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }
}

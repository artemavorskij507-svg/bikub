<?php

namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * List restaurants available for delivery.
     */
    public function index(Request $request): JsonResponse
    {
        $city = $request->query('city');

        $query = Restaurant::query()
            ->active()
            ->orderBy('brand')
            ->orderBy('name');

        if ($city) {
            $query->where('city', $city);
        }

        if ($request->boolean('only_delivery')) {
            $query->where('supports_food_delivery', true);
        }

        $restaurants = $query->get([
            'id',
            'name',
            'brand',
            'slug',
            'cuisine_type',
            'address',
            'city',
            'postcode',
            'country',
            'latitude',
            'longitude',
            'phone',
            'supports_food_delivery',
            'has_home_delivery',
            'has_takeaway',
            'delivery_metadata',
        ]);

        return response()->json([
            'success' => true,
            'data' => $restaurants,
        ]);
    }
}

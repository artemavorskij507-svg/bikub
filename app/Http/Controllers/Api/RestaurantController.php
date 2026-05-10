<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $query = Restaurant::active()->orderBy('name');

        if ($request->has('delivery')) {
            if ($request->delivery === 'true') {
                $query->withHomeDelivery();
            }
        }

        if ($request->has('cuisine')) {
            $query->where('cuisine_type', $request->cuisine);
        }

        $restaurants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $restaurants,
            'count' => $restaurants->count(),
            'message' => 'Restaurants retrieved successfully',
        ]);
    }

    public function show(string $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->first();

        if (! $restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $restaurant,
            'message' => 'Restaurant retrieved successfully',
        ]);
    }
}

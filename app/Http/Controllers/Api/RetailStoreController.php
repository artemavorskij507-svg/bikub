<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RetailStore;
use Illuminate\Http\Request;

class RetailStoreController extends Controller
{
    public function index(Request $request)
    {
        $query = RetailStore::active()->orderBy('name');

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('delivery')) {
            if ($request->delivery === 'true') {
                $query->where('has_home_delivery', true);
            }
        }

        $stores = $query->get();

        return response()->json([
            'success' => true,
            'data' => $stores,
            'count' => $stores->count(),
            'message' => 'Retail stores retrieved successfully',
        ]);
    }

    public function show(string $slug)
    {
        $store = RetailStore::where('slug', $slug)->first();

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $store,
            'message' => 'Store retrieved successfully',
        ]);
    }
}

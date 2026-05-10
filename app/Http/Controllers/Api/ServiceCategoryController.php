<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ServiceCategory::orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'count' => $categories->count(),
            'message' => 'Categories retrieved successfully',
        ]);
    }

    public function show(string $code)
    {
        $category = ServiceCategory::where('code', $code)->with('serviceTypes')->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category retrieved successfully',
        ]);
    }
}

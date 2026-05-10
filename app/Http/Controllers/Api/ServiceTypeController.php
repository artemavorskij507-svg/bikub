<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;

class ServiceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get service types from database
        $query = ServiceType::query()->orderBy('sort_order');

        // Filter by category if provided
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter active only if requested
        if ($request->boolean('active')) {
            $query->where('is_active', true);
        }

        $serviceTypes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $serviceTypes,
            'count' => $serviceTypes->count(),
            'message' => 'Service types retrieved successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $serviceType = ServiceType::where('slug', $slug)->first();

        if (! $serviceType) {
            return response()->json([
                'success' => false,
                'message' => 'Service type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $serviceType,
            'message' => 'Service type retrieved successfully',
        ]);
    }

    /**
     * Get service types by category.
     */
    public function byCategory(string $category)
    {
        $serviceTypes = ServiceType::query()
            ->byCategory($category)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $serviceTypes,
            'count' => $serviceTypes->count(),
            'message' => "Service types for category '{$category}' retrieved successfully",
        ]);
    }
}

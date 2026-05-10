<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = Partner::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('active')) {
            $query->where('is_active', (bool) $request->active);
        }

        $partners = $query->get();

        return response()->json([
            'success' => true,
            'data' => $partners,
            'count' => $partners->count(),
            'message' => 'Partners retrieved successfully',
        ]);
    }

    public function show(string $slug)
    {
        $partner = Partner::where('slug', $slug)->first();

        if (! $partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $partner,
            'message' => 'Partner retrieved successfully',
        ]);
    }
}

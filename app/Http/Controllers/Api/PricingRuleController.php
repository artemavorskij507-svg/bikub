<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PricingRule;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = PricingRule::with('serviceType');

        if ($request->has('service_type_id')) {
            $query->where('service_type_id', $request->service_type_id);
        }

        $rules = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rules,
            'count' => $rules->count(),
            'message' => 'Pricing rules retrieved successfully',
        ]);
    }

    public function show(int $id)
    {
        $rule = PricingRule::with('serviceType')->find($id);

        if (! $rule) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing rule not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rule,
            'message' => 'Pricing rule retrieved successfully',
        ]);
    }
}

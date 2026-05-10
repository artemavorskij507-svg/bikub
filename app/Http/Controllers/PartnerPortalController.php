<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerPricingOverride;
use App\Models\PartnerServiceArea;
use App\Models\PartnerSetting;
use App\Models\PartnerStatement;
use App\Models\PartnerUser;
use Illuminate\Http\Request;

class PartnerPortalController extends Controller
{
    /**
     * Get partner profile and available sections.
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $partnerUser = PartnerUser::where('user_id', $user->id)->first();

        if (! $partnerUser) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with any partner',
            ], 403);
        }

        $partner = $partnerUser->partner;
        $settings = $partner->settings ?? new PartnerSetting(['data' => []]);

        return response()->json([
            'success' => true,
            'data' => [
                'partner' => [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'type' => $partner->type,
                    'status' => $partner->status,
                ],
                'user_role' => $partnerUser->role,
                'permissions' => $this->getUserPermissions($partnerUser->role),
                'settings' => $settings->data,
                'available_sections' => $this->getAvailableSections($partnerUser->role),
            ],
        ]);
    }

    /**
     * Get partner settings.
     */
    public function getSettings(Request $request)
    {
        $partner = $this->getPartnerFromUser($request->user());
        $settings = $partner->settings ?? new PartnerSetting(['data' => []]);

        return response()->json([
            'success' => true,
            'data' => $settings->data,
        ]);
    }

    /**
     * Update partner settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'business_hours' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
            'min_order_amount' => 'nullable|numeric|min:0',
            'lead_time_hours' => 'nullable|integer|min:0',
            'auto_accept_orders' => 'nullable|boolean',
        ]);

        $partner = $this->getPartnerFromUser($request->user());

        $settings = PartnerSetting::updateOrCreate(
            ['partner_id' => $partner->id],
            ['data' => $request->all()]
        );

        return response()->json([
            'success' => true,
            'data' => $settings->data,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Get service areas.
     */
    public function getServiceAreas(Request $request)
    {
        $partner = $this->getPartnerFromUser($request->user());
        $areas = $partner->serviceAreas()->with('geoZone')->get();

        return response()->json([
            'success' => true,
            'data' => $areas->map(function ($area) {
                return [
                    'id' => $area->id,
                    'zone' => [
                        'id' => $area->geoZone->id,
                        'name' => $area->geoZone->name,
                        'code' => $area->geoZone->code,
                    ],
                    'capacity' => $area->capacity,
                    'surcharge' => $area->surcharge,
                ];
            }),
        ]);
    }

    /**
     * Create or update service area.
     */
    public function updateServiceArea(Request $request)
    {
        $request->validate([
            'zone_id' => 'required|exists:geo_zones,id',
            'capacity' => 'required|integer|min:0',
            'surcharge' => 'nullable|numeric|min:0',
        ]);

        $partner = $this->getPartnerFromUser($request->user());

        $area = PartnerServiceArea::updateOrCreate(
            [
                'partner_id' => $partner->id,
                'zone_id' => $request->zone_id,
            ],
            [
                'capacity' => $request->capacity,
                'surcharge' => $request->surcharge ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $area->load('geoZone'),
            'message' => 'Service area updated successfully',
        ]);
    }

    /**
     * Get pricing overrides.
     */
    public function getPricingOverrides(Request $request)
    {
        $partner = $this->getPartnerFromUser($request->user());
        $overrides = $partner->pricingOverrides()->with('serviceType')->get();

        return response()->json([
            'success' => true,
            'data' => $overrides->map(function ($override) {
                return [
                    'id' => $override->id,
                    'service_type' => [
                        'id' => $override->serviceType->id,
                        'name' => $override->serviceType->name,
                        'code' => $override->serviceType->code,
                    ],
                    'rule' => $override->rule,
                    'active_from' => $override->active_from,
                    'active_to' => $override->active_to,
                ];
            }),
        ]);
    }

    /**
     * Create pricing override.
     */
    public function createPricingOverride(Request $request)
    {
        $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'rule' => 'required|array',
            'active_from' => 'nullable|date',
            'active_to' => 'nullable|date|after:active_from',
        ]);

        $partner = $this->getPartnerFromUser($request->user());

        $override = PartnerPricingOverride::create([
            'partner_id' => $partner->id,
            'service_type_id' => $request->service_type_id,
            'rule' => $request->rule,
            'active_from' => $request->active_from,
            'active_to' => $request->active_to,
        ]);

        return response()->json([
            'success' => true,
            'data' => $override->load('serviceType'),
            'message' => 'Pricing override created successfully',
        ], 201);
    }

    /**
     * Get partner statements.
     */
    public function getStatements(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
        ]);

        $partner = $this->getPartnerFromUser($request->user());
        $query = $partner->statements();

        if ($request->from) {
            $query->where('period_start', '>=', $request->from);
        }

        if ($request->to) {
            $query->where('period_end', '<=', $request->to);
        }

        $statements = $query->orderBy('period_start', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $statements->map(function ($statement) {
                return [
                    'id' => $statement->id,
                    'period_start' => $statement->period_start,
                    'period_end' => $statement->period_end,
                    'gross_amount' => $statement->gross_amount,
                    'fee_amount' => $statement->fee_amount,
                    'net_amount' => $statement->net_amount,
                    'status' => $statement->status,
                    'breakdown' => $statement->breakdown,
                ];
            }),
        ]);
    }

    /**
     * Generate statement for period.
     */
    public function generateStatement(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after:from',
        ]);

        $partner = $this->getPartnerFromUser($request->user());

        // Check if statement already exists
        $existing = $partner->statements()
            ->where('period_start', $request->from)
            ->where('period_end', $request->to)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Statement for this period already exists',
            ], 400);
        }

        // Calculate statement
        $statement = $this->calculateStatement($partner, $request->from, $request->to);

        return response()->json([
            'success' => true,
            'data' => $statement,
            'message' => 'Statement generated successfully',
        ], 201);
    }

    /**
     * Calculate statement for partner.
     */
    private function calculateStatement(Partner $partner, string $from, string $to): PartnerStatement
    {
        $orders = Order::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'paid')
            ->get();

        $grossAmount = $orders->sum('total_amount');
        $feeAmount = $this->calculateFees($orders, $partner);
        $netAmount = $grossAmount - $feeAmount;

        $breakdown = $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function ($dayOrders) {
            return [
                'date' => $dayOrders->first()->created_at->format('Y-m-d'),
                'orders_count' => $dayOrders->count(),
                'gross_amount' => $dayOrders->sum('total_amount'),
            ];
        });

        return PartnerStatement::create([
            'partner_id' => $partner->id,
            'period_start' => $from,
            'period_end' => $to,
            'gross_amount' => $grossAmount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'status' => 'draft',
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Calculate fees for orders.
     */
    private function calculateFees($orders, Partner $partner): float
    {
        $totalFees = 0;

        foreach ($orders as $order) {
            // Simple fee calculation - 5% of order amount
            // In production, this would be more complex based on partner agreements
            $totalFees += $order->total_amount * 0.05;
        }

        return $totalFees;
    }

    /**
     * Get partner from authenticated user.
     */
    private function getPartnerFromUser($user): Partner
    {
        $partnerUser = PartnerUser::where('user_id', $user->id)->first();

        if (! $partnerUser) {
            throw new \Exception('User is not associated with any partner');
        }

        return $partnerUser->partner;
    }

    /**
     * Get user permissions based on role.
     */
    private function getUserPermissions(string $role): array
    {
        return match ($role) {
            'admin' => ['manage_settings', 'manage_areas', 'manage_pricing', 'view_statements', 'manage_users'],
            'manager' => ['manage_settings', 'manage_areas', 'manage_pricing', 'view_statements'],
            'staff' => ['view_statements'],
            default => [],
        };
    }

    /**
     * Get available sections based on role.
     */
    private function getAvailableSections(string $role): array
    {
        return match ($role) {
            'admin' => ['settings', 'service_areas', 'pricing', 'statements', 'users'],
            'manager' => ['settings', 'service_areas', 'pricing', 'statements'],
            'staff' => ['statements'],
            default => [],
        };
    }
}

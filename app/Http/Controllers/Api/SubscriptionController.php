<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Get available subscription plans
     */
    public function getPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_code' => 'required|string|exists:subscription_plans,code',
            'payment_method' => 'required|string|in:stripe,vipps',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $plan = SubscriptionPlan::where('code', $request->plan_code)->first();

        // Check if user already has an active subscription
        $existingSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription',
            ], 400);
        }

        // Create subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => $this->calculatePeriodEnd($plan->period),
            'meta' => [
                'payment_method' => $request->payment_method,
                'subscribed_at' => now(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'subscription' => $subscription->load('plan'),
            'message' => 'Successfully subscribed to '.$plan->name,
        ]);
    }

    /**
     * Get user's subscription
     */
    public function getSubscription(): JsonResponse
    {
        $user = Auth::user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(): JsonResponse
    {
        $user = Auth::user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
        ]);
    }

    /**
     * Renew subscription
     */
    public function renewSubscription(): JsonResponse
    {
        $user = Auth::user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        $subscription->renew();

        return response()->json([
            'success' => true,
            'subscription' => $subscription->fresh(),
            'message' => 'Subscription renewed successfully',
        ]);
    }

    public function cancel(): JsonResponse
    {
        return $this->cancelSubscription();
    }

    public function getMySubscriptions(): JsonResponse
    {
        return $this->getSubscription();
    }

    /**
     * Calculate period end date
     */
    private function calculatePeriodEnd(string $period): \Carbon\Carbon
    {
        return match ($period) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth()
        };
    }
}

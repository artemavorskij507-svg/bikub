<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    /**
     * Subscribe user to push notifications
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|url',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
            'device_info' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id' => $user->id,
                'public_key' => $request->public_key,
                'auth_token' => $request->auth_token,
                'device_info' => $request->device_info ?? null,
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to push notifications',
            'data' => $subscription,
        ]);
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscription = PushSubscription::where('endpoint', $request->endpoint)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($subscription) {
            $subscription->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from push notifications',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Subscription not found',
        ], 404);
    }

    /**
     * Get user's push subscriptions
     */
    public function subscriptions(Request $request)
    {
        $subscriptions = PushSubscription::where('user_id', $request->user()->id)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'count' => $subscriptions->count(),
        ]);
    }
}

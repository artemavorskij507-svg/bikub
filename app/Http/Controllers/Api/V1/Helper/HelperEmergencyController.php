<?php

namespace App\Http\Controllers\Api\V1\Helper;

use App\Events\SocialCare\SocialCareEmergencyTriggered;
use App\Http\Controllers\Api\V1\Helper\Concerns\ResolvesHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SocialCareEmergencyEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class HelperEmergencyController extends Controller
{
    use ResolvesHelper;

    public function trigger(Request $request): JsonResponse
    {
        $helper = $this->helperProfile();
        $user = $helper->user;

        // Rate limiting: максимум 5 emergency за 5 минут
        $key = 'emergency:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Too many emergency requests. Please wait.',
            ], 429);
        }

        RateLimiter::hit($key, 300); // 5 минут

        $data = $request->validate([
            'order_id' => ['nullable', 'exists:orders,id'],
            'level' => ['nullable', 'in:INFO,WARNING,CRITICAL'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $level = $data['level'] ?? 'WARNING';

        $order = null;
        $clientProfile = null;

        if (! empty($data['order_id'])) {
            $order = Order::with('careDetails.clientProfile')
                ->findOrFail($data['order_id']);

            $clientProfile = $order->careDetails?->clientProfile;
        }

        $emergency = SocialCareEmergencyEvent::create([
            'order_id' => $order?->id,
            'helper_profile_id' => $helper->id,
            'client_profile_id' => $clientProfile?->id,
            'triggered_by_user_id' => $user->id,
            'source' => 'HELPER_APP',
            'level' => $level,
            'message' => $data['message'] ?? null,
        ]);

        event(new SocialCareEmergencyTriggered($emergency));

        return response()->json([
            'data' => [
                'id' => $emergency->id,
                'status' => 'ok',
                'message' => 'Emergency event created and notifications sent',
            ],
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Test notification template.
     */
    public function testTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_code' => 'required|string',
            'channel' => 'required|in:email,sms,push',
            'locale' => 'required|in:ru,no,en',
            'variables' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $result = $this->notificationService->testTemplate(
            $request->template_code,
            $request->channel,
            $request->locale,
            $request->variables
        );

        return response()->json($result);
    }

    /**
     * Get user notification preferences.
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $preferences = NotificationPreference::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $preferences->map(function ($pref) {
                return [
                    'channel' => $pref->channel,
                    'locale' => $pref->locale,
                    'enabled' => $pref->enabled,
                    'meta' => $pref->meta,
                ];
            }),
        ]);
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.*.channel' => 'required|in:email,sms,push',
            'preferences.*.locale' => 'required|in:ru,no,en',
            'preferences.*.enabled' => 'required|boolean',
            'preferences.*.meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = $request->user();

        foreach ($request->preferences as $prefData) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'channel' => $prefData['channel'],
                ],
                [
                    'locale' => $prefData['locale'],
                    'enabled' => $prefData['enabled'],
                    'meta' => $prefData['meta'] ?? [],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Send test notification to user.
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_code' => 'required|string',
            'channel' => 'required|in:email,sms,push',
            'variables' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $user = $request->user();

        $success = $this->notificationService->sendNotification(
            $user,
            $request->template_code,
            $request->channel,
            $request->variables
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Test notification sent' : 'Failed to send notification',
        ]);
    }

    /**
     * Get available notification templates.
     */
    public function getTemplates(Request $request)
    {
        $templates = NotificationTemplate::active()
            ->select('code', 'channel', 'locale', 'subject', 'variables')
            ->get()
            ->groupBy('code');

        return response()->json([
            'success' => true,
            'data' => $templates->map(function ($templateGroup) {
                return [
                    'code' => $templateGroup->first()->code,
                    'channels' => $templateGroup->groupBy('channel')->map(function ($channelGroup) {
                        return [
                            'channel' => $channelGroup->first()->channel,
                            'locales' => $channelGroup->map(function ($template) {
                                return [
                                    'locale' => $template->locale,
                                    'subject' => $template->subject,
                                    'variables' => $template->variables,
                                ];
                            })->keyBy('locale'),
                        ];
                    })->keyBy('channel'),
                ];
            }),
        ]);
    }

    /**
     * Get notification history for user.
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $query = $user->notificationEvents()->orderBy('created_at', 'desc');

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $events = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'channel' => $event->channel,
                    'template_code' => $event->template_code,
                    'status' => $event->status,
                    'sent_at' => $event->sent_at,
                    'created_at' => $event->created_at,
                    'error' => $event->error,
                ];
            }),
        ]);
    }

    /**
     * Send order notification (internal use).
     */
    public function sendOrderNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'event' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $order = \App\Models\Order::findOrFail($request->order_id);

        $this->notificationService->sendOrderNotification($order, $request->event);

        return response()->json([
            'success' => true,
            'message' => 'Order notification sent',
        ]);
    }

    /**
     * Send courier notification (internal use).
     */
    public function sendCourierNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'courier_id' => 'required|exists:users,id',
            'event' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $courier = User::findOrFail($request->courier_id);

        $this->notificationService->sendCourierNotification(
            $courier,
            $request->event,
            $request->variables ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Courier notification sent',
        ]);
    }
}

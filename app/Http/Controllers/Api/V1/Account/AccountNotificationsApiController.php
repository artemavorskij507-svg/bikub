<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\NotificationFeed;
use App\Models\SocialCareNotificationSettings;
use App\Services\Account\TimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountNotificationsApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->socialCareNotificationSettings
            ?? new SocialCareNotificationSettings([
                'notify_care_order_created' => true,
                'notify_care_plan_created' => true,
                'notify_visit_status_changes' => true,
                'notify_visit_reports' => true,
                'notify_emergency' => true,
                'notify_reschedule_requests' => true,
            ]);

        return response()->json([
            'data' => [
                'social_care' => [
                    'notify_care_order_created' => (bool) $settings->notify_care_order_created,
                    'notify_care_plan_created' => (bool) $settings->notify_care_plan_created,
                    'notify_visit_status_changes' => (bool) $settings->notify_visit_status_changes,
                    'notify_visit_reports' => (bool) $settings->notify_visit_reports,
                    'notify_emergency' => (bool) $settings->notify_emergency,
                    'notify_reschedule_requests' => (bool) $settings->notify_reschedule_requests,
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $category = $request->string('category')->toString();
        $onlyUnread = $request->boolean('unread');

        $query = NotificationFeed::where('user_id', $user->id)->latest('created_at');

        if ($category) {
            $query->where('category', $category);
        }

        if ($onlyUnread) {
            $query->whereNull('read_at');
        }

        $paginator = $query->paginate(20);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (NotificationFeed $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'category' => $notification->category,
                'title' => $notification->title,
                'body' => $notification->body,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:notification_feeds,id'],
        ]);

        $notification = NotificationFeed::where('user_id', $request->user()->id)
            ->where('id', $data['id'])
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        NotificationFeed::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    public function timeline(Request $request, TimelineService $timelineService): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 200);
        $timeline = $timelineService->getTimelineForUser($request->user(), $limit);

        return response()->json([
            'data' => $timeline,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\NotificationFeed;
use App\Services\Notifications\NotificationFeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationInboxController extends Controller
{
    public function index(Request $request): View
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

        $notifications = $query->paginate(20)->withQueryString();

        return view('account.notifications.inbox', [
            'notifications' => $notifications,
            'currentCategory' => $category,
            'onlyUnread' => $onlyUnread,
        ]);
    }

    public function markRead(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:notification_feeds,id'],
        ]);

        $notification = NotificationFeed::where('user_id', $request->user()->id)
            ->where('id', $data['id'])
            ->firstOrFail();

        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request, NotificationFeedService $feedService): RedirectResponse
    {
        $feedService->markAllAsRead($request->user());

        return back()->with('status', 'Все уведомления отмечены как прочитанные');
    }
}

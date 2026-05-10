<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(50)
            ->get();

        return view('account.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification)
    {
        $user = $request->user();

        if ($notification->notifiable_id !== $user->id || $notification->notifiable_type !== get_class($user)) {
            abort(403);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return back();
    }
}

<?php

namespace App\Services\Account;

use App\Models\NotificationFeed;
use App\Models\User;
use Illuminate\Support\Collection;

class TimelineService
{
    public function getTimelineForUser(User $user, int $limit = 100): Collection
    {
        return NotificationFeed::where('user_id', $user->id)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (NotificationFeed $notification) {
                return [
                    'id' => $notification->id,
                    'created_at' => $notification->created_at,
                    'type' => $notification->type,
                    'category' => $notification->category,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                ];
            });
    }
}

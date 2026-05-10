<?php

namespace App\Services\Notifications;

use App\Models\NotificationFeed;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationFeedService
{
    public function push(
        User $user,
        string $type,
        ?string $category,
        string $title,
        ?string $body = null,
        ?Model $notifiable = null,
        array $data = []
    ): NotificationFeed {
        $payload = [
            'user_id' => $user->id,
            'type' => $type,
            'category' => $category,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ];

        if ($notifiable) {
            $payload['notifiable_type'] = $notifiable::class;
            $payload['notifiable_id'] = $notifiable->getKey();
        }

        return NotificationFeed::create($payload);
    }

    public function markAllAsRead(User $user): void
    {
        NotificationFeed::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}

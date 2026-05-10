<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WorkerPayoutStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Payout $payout,
        public string $oldStatus
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'payout.status_changed',
            'payout_id' => $this->payout->id,
            'amount' => $this->payout->amount,
            'currency' => $this->payout->currency,
            'old_status' => $this->oldStatus,
            'new_status' => $this->payout->status,
            'method' => $this->payout->method,
            'updated_at' => $this->payout->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WorkerPayoutRequested extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Payout $payout
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
            'type' => 'payout.requested',
            'payout_id' => $this->payout->id,
            'user_id' => $this->payout->user_id,
            'amount' => $this->payout->amount,
            'currency' => $this->payout->currency,
            'status' => $this->payout->status,
            'method' => $this->payout->method,
            'created_at' => $this->payout->created_at?->toIso8601String(),
            'worker_name' => $this->payout->user?->name,
        ];
    }
}

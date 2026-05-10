<?php

namespace App\Observers;

use App\Events\PayoutPaid;
use App\Models\Payout;
use App\Notifications\WorkerPayoutStatusChanged;

class PayoutObserver
{
    /**
     * Зберігаємо старий статус для кожного payout між викликами updating() та updated()
     */
    private static array $oldStatuses = [];

    public function updating(Payout $payout)
    {
        $originalStatus = $payout->getOriginal('status');
        $newStatus = $payout->status;

        // Якщо статус реально змінився, зберігаємо старий статус
        if ($originalStatus !== $newStatus) {
            self::$oldStatuses[$payout->id] = $originalStatus;
        }
    }

    public function updated(Payout $payout)
    {
        // Перевіряємо, чи був збережений старий статус для цього payout
        if (! isset(self::$oldStatuses[$payout->id])) {
            return;
        }

        $oldStatus = self::$oldStatuses[$payout->id];

        // Видаляємо з кешу після використання
        unset(self::$oldStatuses[$payout->id]);

        // Відправляємо нотифікацію тільки при "значущих" змінах
        if (in_array($payout->status, ['paid', 'completed', 'rejected', 'cancelled', 'processing'], true)) {
            $user = $payout->user;

            if ($user) {
                $user->notify(new WorkerPayoutStatusChanged($payout, $oldStatus));
            }
        }

        if (in_array($payout->status, ['paid', 'completed'], true)) {
            event(new PayoutPaid($payout));
        }
    }
}

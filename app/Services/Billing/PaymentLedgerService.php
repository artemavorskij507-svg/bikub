<?php

namespace App\Services\Billing;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Notifications\NotificationFeedService;

class PaymentLedgerService
{
    public function __construct(
        protected NotificationFeedService $feed
    ) {}

    public function recordCharge(
        User $user,
        ?Order $order,
        int $amountMinor,
        string $currency,
        string $provider,
        ?string $paymentId,
        ?string $chargeId,
        ?string $label = null,
        array $meta = []
    ): PaymentTransaction {
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'type' => 'charge',
            'currency' => $currency,
            'amount_minor' => $amountMinor,
            'provider' => $provider,
            'provider_payment_id' => $paymentId,
            'provider_charge_id' => $chargeId,
            'status' => 'succeeded',
            'label' => $label,
            'meta' => $meta,
            'processed_at' => now(),
        ]);

        $this->pushTimelineEvent(
            $user,
            'billing.charge_captured',
            "Оплата заказа #{$order?->id} подтверждена",
            $order,
            $transaction,
            $label
        );

        return $transaction;
    }

    public function recordRefund(
        User $user,
        ?Order $order,
        int $amountMinor,
        string $currency,
        string $provider,
        ?string $paymentId,
        ?string $refundId,
        ?string $label = null,
        array $meta = []
    ): PaymentTransaction {
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'type' => 'refund',
            'currency' => $currency,
            'amount_minor' => $amountMinor * -1,
            'provider' => $provider,
            'provider_payment_id' => $paymentId,
            'provider_charge_id' => $refundId,
            'status' => 'succeeded',
            'label' => $label,
            'meta' => $meta,
            'processed_at' => now(),
        ]);

        $this->pushTimelineEvent(
            $user,
            'billing.refund_processed',
            "Возврат по заказу #{$order?->id}",
            $order,
            $transaction,
            $label
        );

        return $transaction;
    }

    public function recordTip(
        User $user,
        ?Order $order,
        int $amountMinor,
        string $currency,
        string $provider,
        ?string $paymentId,
        ?string $chargeId,
        ?string $label = null,
        array $meta = []
    ): PaymentTransaction {
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'type' => 'tip',
            'currency' => $currency,
            'amount_minor' => $amountMinor,
            'provider' => $provider,
            'provider_payment_id' => $paymentId,
            'provider_charge_id' => $chargeId,
            'status' => 'succeeded',
            'label' => $label,
            'meta' => $meta,
            'processed_at' => now(),
        ]);

        $this->pushTimelineEvent(
            $user,
            'billing.tip_recorded',
            "Чаевые по заказу #{$order?->id}",
            $order,
            $transaction,
            $label
        );

        return $transaction;
    }

    protected function pushTimelineEvent(
        User $user,
        string $type,
        string $title,
        ?Order $order,
        PaymentTransaction $transaction,
        ?string $body = null
    ): void {
        $this->feed->push(
            $user,
            $type,
            'billing',
            $title,
            $body,
            $order,
            [
                'payment_transaction_id' => $transaction->id,
                'order_id' => $order?->id,
                'amount' => $transaction->amount,
            ]
        );
    }
}

<?php

namespace App\Listeners;

use App\Events\OrderPlaced;

class ProcessOrderPayment
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * Обробляє платіж за замовлення через Cashier
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (! $user || ! $order->total_amount || ! $order->payment_method) {
            return;
        }

        try {
            // Конвертуємо суму в центи для Cashier
            $amountInCents = (int) ($order->total_amount * 100);

            // Використовуємо Cashier для обробки платежу
            $payment = $user->charge(
                $amountInCents,
                $order->payment_method ?? 'default',
                [
                    'description' => 'Payment for Order #'.$order->order_number,
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ],
                ]
            );

            // Оновлюємо статус замовлення
            $order->update([
                'payment_status' => 'paid',
                'payment_intent_id' => $payment->id ?? null,
            ]);

            // Логування успішного платежу
            activity()
                ->performedOn($order)
                ->by($user)
                ->event('payment_processed')
                ->withProperties([
                    'amount' => $order->total_amount,
                    'payment_id' => $payment->id ?? null,
                    'method' => 'cashier',
                ])
                ->log('Order payment successful.');

        } catch (\Exception $e) {
            // Оновлюємо статус на failed
            $order->update([
                'payment_status' => 'failed',
            ]);

            // Логування помилки
            activity()
                ->performedOn($order)
                ->by($user)
                ->event('payment_failed')
                ->withProperties([
                    'amount' => $order->total_amount,
                    'error' => $e->getMessage(),
                ])
                ->log('Order payment failed: '.$e->getMessage());

            // Кидаємо винятком для обробки в контролері
            throw $e;
        }
    }
}

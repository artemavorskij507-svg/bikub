<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Coupon;
use App\Models\LoyaltyBalance;
use App\Models\LoyaltyTransaction;

class ApplyLoyaltyAndPromocodes
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
     * Обробляє промокоди та бали лояльності
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (! $user) {
            return;
        }

        $discountAmount = 0;

        // 1. Обробка Промокоду
        if ($order->coupon_code) {
            $discountAmount = $this->applyCoupon($order, $user);
        }

        // 2. Обробка Балів Лояльності
        if (isset($order->points_to_redeem) && $order->points_to_redeem > 0) {
            $pointsDiscount = $this->redeemLoyaltyPoints($order, $user);
            $discountAmount += $pointsDiscount;
        }

        // 3. Нарахування нових балів за замовлення
        $this->awardLoyaltyPoints($order, $user);

        // Оновлюємо остаточну суму замовлення
        if ($discountAmount > 0) {
            $order->update([
                'final_price' => max(0, $order->total_amount - $discountAmount),
                'discount_amount' => $discountAmount,
            ]);

            activity()
                ->performedOn($order)
                ->by($user)
                ->event('discount_applied')
                ->withProperties([
                    'discount_amount' => $discountAmount,
                    'coupon_code' => $order->coupon_code,
                    'points_redeemed' => $order->points_to_redeem ?? 0,
                ])
                ->log('Discounts applied to order.');
        }
    }

    /**
     * Застосування промокоду до замовлення
     */
    private function applyCoupon($order, $user): float
    {
        try {
            $coupon = Coupon::where('code', $order->coupon_code)
                ->where('is_active', true)
                ->first();

            if (! $coupon) {
                activity()
                    ->performedOn($order)
                    ->by($user)
                    ->event('coupon_not_found')
                    ->withProperties(['coupon_code' => $order->coupon_code])
                    ->log('Coupon not found: '.$order->coupon_code);

                return 0;
            }

            // Перевіряємо, чи coupon ще дійсний
            if ($coupon->expired_at && $coupon->expired_at < now()) {
                activity()
                    ->performedOn($order)
                    ->by($user)
                    ->event('coupon_expired')
                    ->withProperties(['coupon_code' => $order->coupon_code])
                    ->log('Coupon expired: '.$order->coupon_code);

                return 0;
            }

            // Перевіряємо максимальне використання
            if ($coupon->max_uses && $coupon->times_used >= $coupon->max_uses) {
                activity()
                    ->performedOn($order)
                    ->by($user)
                    ->event('coupon_limit_reached')
                    ->withProperties(['coupon_code' => $order->coupon_code])
                    ->log('Coupon usage limit reached: '.$order->coupon_code);

                return 0;
            }

            // Розраховуємо знижку
            $discountAmount = 0;
            if ($coupon->discount_type === 'percentage') {
                $discountAmount = ($order->total_amount * $coupon->discount_value) / 100;
            } else {
                $discountAmount = $coupon->discount_value;
            }

            // Додаємо максимальне обмеження, якщо існує
            if ($coupon->max_discount) {
                $discountAmount = min($discountAmount, $coupon->max_discount);
            }

            // Збільшуємо лічильник використання
            $coupon->increment('times_used');

            activity()
                ->performedOn($order)
                ->by($user)
                ->event('coupon_applied')
                ->withProperties([
                    'coupon_code' => $order->coupon_code,
                    'discount_amount' => $discountAmount,
                ])
                ->log('Coupon applied: '.$order->coupon_code);

            return $discountAmount;

        } catch (\Exception $e) {
            activity()
                ->performedOn($order)
                ->by($user)
                ->event('coupon_error')
                ->withProperties(['error' => $e->getMessage()])
                ->log('Error applying coupon: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Списання балів лояльності
     */
    private function redeemLoyaltyPoints($order, $user): float
    {
        try {
            // Отримуємо поточний баланс балів
            $loyaltyBalance = LoyaltyBalance::where('user_id', $user->id)->first();

            if (! $loyaltyBalance || $loyaltyBalance->balance < $order->points_to_redeem) {
                activity()
                    ->performedOn($order)
                    ->by($user)
                    ->event('insufficient_loyalty_points')
                    ->withProperties(['requested' => $order->points_to_redeem])
                    ->log('Insufficient loyalty points.');

                return 0;
            }

            // Розраховуємо грошову вартість балів (наприклад, 1 бал = 0.01 CHF)
            $pointValue = 0.01;
            $pointsDiscount = $order->points_to_redeem * $pointValue;

            // Оновлюємо баланс
            $loyaltyBalance->decrement('balance', $order->points_to_redeem);

            // Записуємо транзакцію
            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => 'redeemed',
                'points' => -$order->points_to_redeem,
                'amount' => $pointsDiscount,
                'description' => 'Points redeemed for Order #'.$order->order_number,
                'order_id' => $order->id,
                'metadata' => [
                    'point_value' => $pointValue,
                ],
            ]);

            activity()
                ->performedOn($order)
                ->by($user)
                ->event('loyalty_points_redeemed')
                ->withProperties([
                    'points_redeemed' => $order->points_to_redeem,
                    'discount_amount' => $pointsDiscount,
                ])
                ->log('Loyalty points redeemed.');

            return $pointsDiscount;

        } catch (\Exception $e) {
            activity()
                ->performedOn($order)
                ->by($user)
                ->event('loyalty_redemption_error')
                ->withProperties(['error' => $e->getMessage()])
                ->log('Error redeeming loyalty points: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Нарахування нових балів за замовлення
     */
    private function awardLoyaltyPoints($order, $user): void
    {
        try {
            // Розраховуємо кількість балів на основі суми замовлення
            // Наприклад: 1 бал за кожні 10 CHF витрачено
            $pointsEarned = (int) floor($order->total_amount / 10);

            if ($pointsEarned <= 0) {
                return;
            }

            // Отримуємо або створюємо баланс
            $loyaltyBalance = LoyaltyBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // Збільшуємо баланс
            $loyaltyBalance->increment('balance', $pointsEarned);

            // Записуємо транзакцію
            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'type' => 'earned',
                'points' => $pointsEarned,
                'amount' => $order->total_amount,
                'description' => 'Points earned for Order #'.$order->order_number,
                'order_id' => $order->id,
                'metadata' => [
                    'points_per_currency' => 0.1, // 1 бал на 10 CHF
                ],
            ]);

            activity()
                ->performedOn($order)
                ->by($user)
                ->event('loyalty_points_earned')
                ->withProperties([
                    'points_earned' => $pointsEarned,
                    'balance_after' => $loyaltyBalance->balance,
                ])
                ->log('Loyalty points earned: '.$pointsEarned.' points.');

        } catch (\Exception $e) {
            activity()
                ->performedOn($order)
                ->by($user)
                ->event('loyalty_award_error')
                ->withProperties(['error' => $e->getMessage()])
                ->log('Error awarding loyalty points: '.$e->getMessage());
        }
    }
}

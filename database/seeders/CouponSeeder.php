<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME10',
                'name' => 'Welcome 10%',
                'type' => 'percent',
                'value' => 10.00,
                'max_uses' => 100,
                'used' => 0,
                'minimum_order_amount' => 100.00,
                'valid_from' => now(),
                'valid_to' => now()->addMonths(3),
                'applicable_categories' => ['care', 'eco', 'market'],
                'is_active' => true,
            ],
            [
                'code' => 'FIRST50',
                'name' => 'First Order 50 NOK',
                'type' => 'fixed',
                'value' => 50.00,
                'max_uses' => 200,
                'used' => 15,
                'minimum_order_amount' => 150.00,
                'valid_from' => now()->subDays(10),
                'valid_to' => now()->addMonths(2),
                'applicable_categories' => null,
                'is_active' => true,
            ],
            [
                'code' => 'FREEDELIVERY',
                'name' => 'Free Delivery',
                'type' => 'free_delivery',
                'value' => 0,
                'max_uses' => null,
                'used' => 45,
                'minimum_order_amount' => 200.00,
                'valid_from' => now(),
                'valid_to' => now()->addMonths(1),
                'applicable_categories' => ['food', 'market'],
                'is_active' => true,
            ],
            [
                'code' => 'WINTER2024',
                'name' => 'Winter Special 15%',
                'type' => 'percent',
                'value' => 15.00,
                'max_uses' => 500,
                'used' => 120,
                'minimum_order_amount' => 300.00,
                'valid_from' => now()->subMonths(1),
                'valid_to' => now()->addMonths(1),
                'applicable_categories' => ['care', 'eco'],
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED20',
                'name' => 'Expired Coupon (for testing)',
                'type' => 'percent',
                'value' => 20.00,
                'max_uses' => 50,
                'used' => 12,
                'minimum_order_amount' => 100.00,
                'valid_from' => now()->subMonths(2),
                'valid_to' => now()->subDays(5),
                'applicable_categories' => null,
                'is_active' => false,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(
                ['code' => $coupon['code']],
                array_merge(['id' => (string) Str::uuid()], $coupon)
            );
        }
    }
}

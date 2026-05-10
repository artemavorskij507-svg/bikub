<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'basic_monthly',
                'name' => 'Basic Monthly',
                'description' => 'Basic plan with monthly billing',
                'period' => 'monthly',
                'price' => 299.00,
                'features' => [
                    'max_orders_per_month' => 50,
                    'support_level' => 'email',
                    'analytics' => true,
                ],
                'is_active' => true,
            ],
            [
                'code' => 'pro_monthly',
                'name' => 'Pro Monthly',
                'description' => 'Professional plan with monthly billing',
                'period' => 'monthly',
                'price' => 599.00,
                'features' => [
                    'max_orders_per_month' => 200,
                    'support_level' => 'priority',
                    'analytics' => true,
                    'api_access' => true,
                ],
                'is_active' => true,
            ],
            [
                'code' => 'business_yearly',
                'name' => 'Business Yearly',
                'description' => 'Business plan with yearly billing',
                'period' => 'yearly',
                'price' => 5999.00,
                'features' => [
                    'max_orders_per_month' => -1,
                    'support_level' => 'dedicated',
                    'analytics' => true,
                    'api_access' => true,
                    'white_label' => true,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            $existing = SubscriptionPlan::where('code', $plan['code'])->first();

            if ($existing && ! Str::isUuid($existing->id)) {
                $existing->delete();
                $existing = null;
            }

            $payload = $plan;
            $payload['id'] = $existing?->id ?? (string) Str::uuid();

            SubscriptionPlan::updateOrCreate(
                ['code' => $plan['code']],
                $payload
            );
        }

        $users = User::limit(5)->get();
        $basicPlan = SubscriptionPlan::where('code', 'basic_monthly')->first();
        $proPlan = SubscriptionPlan::where('code', 'pro_monthly')->first();

        foreach ($users as $index => $user) {
            $plan = $index % 2 === 0 ? $basicPlan : $proPlan;

            if ($plan) {
                $existing = Subscription::where('user_id', $user->id)
                    ->where('plan_id', $plan->id)
                    ->first();

                Subscription::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                    ],
                    [
                        'id' => $existing?->id ?? (string) Str::uuid(),
                        'status' => 'active',
                        'current_period_start' => now()->subDays(rand(1, 30)),
                        'current_period_end' => now()->addDays(rand(1, 30)),
                        'meta' => [
                            'auto_renew' => true,
                            'payment_method' => 'stripe',
                        ],
                    ]
                );
            }
        }
    }
}

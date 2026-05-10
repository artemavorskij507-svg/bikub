<?php

namespace Database\Factories;

use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CareOrderDetails>
 */
class CareOrderDetailsFactory extends Factory
{
    protected $model = CareOrderDetails::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'client_profile_id' => ClientProfile::factory(),
            'care_service_id' => CareService::factory(),
            'care_status' => 'SCHEDULED',
            'scheduled_start_at' => now()->addDay(),
            'scheduled_end_at' => now()->addDay()->addHour(),
        ];
    }
}

<?php

namespace Tests\Unit\Services;

use App\Enums\ServiceType;
use App\Models\CareOrderDetails;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\User;
use App\Services\SocialCare\CareAccountReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareAccountReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_care_order_only_when_related(): void
    {
        $service = app(CareAccountReadService::class);

        $user = User::factory()->create();
        $clientProfile = ClientProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'service_type' => ServiceType::SOCIAL_CARE_VISIT->value,
        ]);

        CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $clientProfile->id,
        ]);

        $this->assertTrue($service->userCanAccessCareOrder($user, $order));

        $unauthorizedUser = User::factory()->create();

        $this->assertFalse($service->userCanAccessCareOrder($unauthorizedUser, $order));
    }
}

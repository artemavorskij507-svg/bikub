<?php

namespace Tests\Unit\Handyman;

use App\Models\ExecutorProfile;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Order;
use App\Models\User;
use App\Services\Handyman\HandymanMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandymanMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HandymanMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HandymanMatchingService;
    }

    public function test_matching_filters_only_active_executors(): void
    {
        $user = User::factory()->create();
        $activeExecutor = ExecutorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
        $inactiveExecutor = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => false,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $details = HandymanOrderDetails::factory()->create(['order_id' => $order->id]);

        $candidates = $this->service->findCandidates($order, $details, null);

        $this->assertTrue($candidates->contains($activeExecutor));
        $this->assertFalse($candidates->contains($inactiveExecutor));
    }

    public function test_matching_prefers_executors_with_required_skills(): void
    {
        $user = User::factory()->create();
        $service = HandymanService::factory()->create([
            'required_skills' => ['plumbing', 'electrical'],
        ]);

        $executorWithSkills = ExecutorProfile::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'skills' => ['plumbing', 'electrical', 'carpentry'],
        ]);

        $executorWithoutSkills = ExecutorProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
            'skills' => ['carpentry'],
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $details = HandymanOrderDetails::factory()->create([
            'order_id' => $order->id,
            'handyman_service_id' => $service->id,
        ]);

        $candidates = $this->service->findCandidates($order, $details, $service);

        $this->assertTrue($candidates->contains($executorWithSkills));
        $this->assertFalse($candidates->contains($executorWithoutSkills));
    }

    public function test_matching_returns_empty_collection_when_no_candidates(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $details = HandymanOrderDetails::factory()->create(['order_id' => $order->id]);

        // Нет активных исполнителей
        $candidates = $this->service->findCandidates($order, $details, null);

        $this->assertEmpty($candidates);
    }

    public function test_matching_respects_limit(): void
    {
        $user = User::factory()->create();
        ExecutorProfile::factory()->count(15)->create([
            'user_id' => fn () => User::factory()->create()->id,
            'is_active' => true,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $details = HandymanOrderDetails::factory()->create(['order_id' => $order->id]);

        $candidates = $this->service->findCandidates($order, $details, null, 5);

        $this->assertCount(5, $candidates);
    }
}

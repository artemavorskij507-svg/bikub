<?php

namespace Tests\Unit;

use App\Models\PricingRule;
use App\Services\Pricing\DemandService;
use App\Services\Pricing\OrderContext;
use App\Services\Pricing\PriceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_and_distance_rules_are_applied(): void
    {
        PricingRule::create([
            'name' => 'Base',
            'slug' => 'test-base',
            'type' => 'base_fee',
            'value' => 50,
            'base_price' => 50,
            'currency' => 'NOK',
            'priority' => 10,
            'active' => true,
        ]);

        PricingRule::create([
            'name' => 'Distance',
            'slug' => 'test-distance',
            'type' => 'distance',
            'value' => 10,
            'base_price' => 0,
            'currency' => 'NOK',
            'priority' => 20,
            'active' => true,
        ]);

        $context = new OrderContext(serviceType: 'errand', distanceKm: 3.5, zone: null);

        $result = app(PriceEngine::class)->estimate($context);

        $this->assertSame(85.0, $result->total);
        $this->assertCount(2, $result->breakdown);
    }

    public function test_time_multiplier_respects_hours_condition(): void
    {
        PricingRule::create([
            'name' => 'Base',
            'slug' => 'evening-base',
            'type' => 'base_fee',
            'value' => 100,
            'base_price' => 100,
            'currency' => 'NOK',
            'priority' => 1,
            'active' => true,
        ]);

        PricingRule::create([
            'name' => 'Evening Bonus',
            'slug' => 'evening-bonus',
            'type' => 'time_multiplier',
            'value' => 20,
            'base_price' => 0,
            'currency' => 'NOK',
            'conditions' => ['hours' => [18, 22]],
            'priority' => 5,
            'active' => true,
        ]);

        $context = new OrderContext(
            serviceType: 'errand',
            scheduledAt: now()->setTime(19, 0)
        );

        $result = app(PriceEngine::class)->estimate($context);

        $this->assertSame(120.0, $result->total);
        $this->assertSame(2, count($result->breakdown));
    }

    public function test_demand_multiplier_is_added_when_zone_hot(): void
    {
        PricingRule::create([
            'name' => 'Base',
            'slug' => 'demand-base',
            'type' => 'base_fee',
            'value' => 100,
            'base_price' => 100,
            'currency' => 'NOK',
            'priority' => 1,
            'active' => true,
        ]);

        app(DemandService::class)->storeMetrics('hot-zone', [
            'requests_per_minute' => 60,
        ]);

        $context = new OrderContext(serviceType: 'errand', zone: 'hot-zone');

        $result = app(PriceEngine::class)->estimate($context);

        $this->assertGreaterThan(100, $result->total);
        $this->assertSame('hot-zone', $context->zone);
        $this->assertTrue(collect($result->breakdown)->pluck('type')->contains('demand_multiplier'));
    }
}

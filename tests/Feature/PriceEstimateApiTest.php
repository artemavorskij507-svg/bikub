<?php

namespace Tests\Feature;

use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceEstimateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_price_estimate_payload(): void
    {
        PricingRule::create([
            'name' => 'API Base',
            'slug' => 'api-base',
            'type' => 'base_fee',
            'value' => 55,
            'base_price' => 55,
            'currency' => 'NOK',
            'priority' => 10,
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/price/estimate', [
            'service_type' => 'errand',
            'zone' => 'narvik',
            'distance_km' => 2.5,
            'scheduled_at' => now()->toIso8601String(),
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'subtotal',
                'total',
                'currency',
                'breakdown',
                'duration_ms',
            ])
            ->assertJson([
                'subtotal' => 55.0,
                'total' => 55.0,
                'currency' => 'NOK',
            ]);
    }
}

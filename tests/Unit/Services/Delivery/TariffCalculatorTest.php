<?php

namespace Tests\Unit\Services\Delivery;

use App\Enums\DeliveryType;
use App\Models\GeoZone;
use App\Models\PricingRule;
use App\Models\ServiceType;
use App\Services\Delivery\TariffCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TariffCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_bulky_quote_uses_volume_based_pricing_rule(): void
    {
        $serviceType = $this->createServiceType('bulky');

        $zone = GeoZone::factory()->create([
            'center_latitude' => 68.5,
            'center_longitude' => 17.4,
            'radius_meters' => 5000,
            'metadata' => [
                'pricing_modifier' => 1,
                'allowed_types' => [DeliveryType::BULKY->value],
            ],
        ]);

        PricingRule::create([
            'service_type_id' => $serviceType->id,
            'name' => 'Small cargo',
            'base_price' => 150,
            'currency' => 'NOK',
            'conditions' => [
                'min_volume' => 0.2,
                'max_volume' => 1.5,
                'geo_zone_id' => [$zone->id],
            ],
            'is_active' => true,
        ]);

        $calculator = app(TariffCalculator::class);

        $quote = $calculator->calculateQuote('bulky', [
            'dimensions' => [
                'length' => 100,
                'width' => 100,
                'height' => 100,
            ],
            'services' => [],
            'pickup_location' => [
                'lat' => $zone->center_latitude,
                'lng' => $zone->center_longitude,
            ],
            'delivery_location' => [
                'lat' => $zone->center_latitude,
                'lng' => $zone->center_longitude,
            ],
        ]);

        $this->assertSame(150.0, $quote['breakdown']['base_rate']);
        $this->assertSame($zone->id, $quote['meta']['geo_zone_id']);
    }

    public function test_grocery_quote_prefers_zone_specific_rule(): void
    {
        $serviceType = $this->createServiceType('grocery');

        $zone = GeoZone::factory()->create([
            'center_latitude' => 68.3,
            'center_longitude' => 17.3,
            'radius_meters' => 5000,
            'metadata' => [
                'allowed_types' => [DeliveryType::GROCERY->value],
            ],
        ]);

        PricingRule::create([
            'service_type_id' => $serviceType->id,
            'name' => 'City center grocery fee',
            'base_price' => 200,
            'currency' => 'NOK',
            'pricing_model' => [
                'type' => 'fixed',
            ],
            'conditions' => [
                'geo_zone_id' => [$zone->id],
            ],
            'is_active' => true,
        ]);

        $calculator = app(TariffCalculator::class);

        $quote = $calculator->calculateQuote('grocery', [
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                    'unit_price' => 75,
                ],
            ],
            'pickup_location' => [
                'lat' => $zone->center_latitude,
                'lng' => $zone->center_longitude,
            ],
            'delivery_location' => [
                'lat' => $zone->center_latitude,
                'lng' => $zone->center_longitude,
            ],
        ]);

        $this->assertSame(200.0, $quote['breakdown']['delivery_fee']);
        $this->assertSame(275.0, $quote['total']);
    }

    protected function createServiceType(string $code): ServiceType
    {
        return ServiceType::create([
            'code' => $code,
            'name' => ucfirst($code),
            'slug' => $code,
            'is_active' => true,
        ]);
    }
}

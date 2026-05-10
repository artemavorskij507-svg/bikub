<?php

namespace Tests\Unit\Services\Delivery;

use App\Enums\DeliveryType;
use App\Models\GeoZone;
use App\Services\Delivery\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GeofenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_find_geo_zone_respects_delivery_type_metadata(): void
    {
        $latitude = 68.4387;
        $longitude = 17.4273;

        $groceryZone = GeoZone::factory()->create([
            'center_latitude' => $latitude,
            'center_longitude' => $longitude,
            'radius_meters' => 1000,
            'metadata' => [
                'allowed_types' => [DeliveryType::GROCERY->value],
            ],
        ]);

        $bulkyZone = GeoZone::factory()->create([
            'center_latitude' => $latitude,
            'center_longitude' => $longitude,
            'radius_meters' => 1000,
            'metadata' => [
                'allowed_types' => [DeliveryType::BULKY->value],
            ],
        ]);

        $service = app(GeofenceService::class);

        $groceryResult = $service->findGeoZone($latitude, $longitude, DeliveryType::GROCERY);
        $bulkyResult = $service->findGeoZone($latitude, $longitude, DeliveryType::BULKY);

        $this->assertTrue($groceryZone->is($groceryResult));
        $this->assertTrue($bulkyZone->is($bulkyResult));
    }
}

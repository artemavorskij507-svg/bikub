<?php

namespace Tests\Unit;

use App\Models\GeoZone;
use App\Services\Geo\GeoZoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoZoneServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GeoZoneService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GeoZoneService::class);
    }

    public function test_point_in_circle_returns_true(): void
    {
        $zone = GeoZone::create([
            'name' => 'Test Circle',
            'slug' => 'test-circle',
            'type' => 'circle',
            'geometry' => [
                'center' => [68.43886, 17.42754],
                'radius_m' => 10000, // 10km
            ],
            'is_active' => true,
            'priority' => 100,
        ]);

        // Point inside circle
        $result = $this->service->findZonesForPoint(68.43886, 17.42754);
        $this->assertTrue($result->contains('id', $zone->id));

        // Point outside circle (far away)
        $result = $this->service->findZonesForPoint(70.0, 20.0);
        $this->assertFalse($result->contains('id', $zone->id));
    }

    public function test_point_in_polygon_returns_true(): void
    {
        $zone = GeoZone::create([
            'name' => 'Test Polygon',
            'slug' => 'test-polygon',
            'type' => 'polygon',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [17.4000, 68.4300],
                    [17.4500, 68.4300],
                    [17.4500, 68.4450],
                    [17.4000, 68.4450],
                    [17.4000, 68.4300],
                ]],
            ],
            'is_active' => true,
            'priority' => 100,
        ]);

        // Point inside polygon
        $result = $this->service->findZonesForPoint(68.4375, 17.4250);
        $this->assertTrue($result->contains('id', $zone->id));

        // Point outside polygon
        $result = $this->service->findZonesForPoint(68.5000, 17.5000);
        $this->assertFalse($result->contains('id', $zone->id));
    }

    public function test_find_zones_returns_sorted_by_priority(): void
    {
        GeoZone::create([
            'name' => 'Low Priority',
            'slug' => 'low-priority',
            'type' => 'circle',
            'geometry' => ['center' => [68.43886, 17.42754], 'radius_m' => 60000],
            'is_active' => true,
            'priority' => 100,
        ]);

        GeoZone::create([
            'name' => 'High Priority',
            'slug' => 'high-priority',
            'type' => 'circle',
            'geometry' => ['center' => [68.43886, 17.42754], 'radius_m' => 60000],
            'is_active' => true,
            'priority' => 10,
        ]);

        $result = $this->service->findZonesForPoint(68.43886, 17.42754);

        $this->assertGreaterThanOrEqual(2, $result->count());
        $this->assertEquals('High Priority', $result->first()->name);
    }
}

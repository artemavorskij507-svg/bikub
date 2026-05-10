<?php

namespace Tests\Unit;

use App\Services\Routing\Point;
use App\Services\Routing\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RoutingServiceFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected RoutingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable OSRM and Mapbox to force fallback
        Config::set('routing.default_provider', 'internal');
        Config::set('routing.osrm.url', null);
        Config::set('routing.mapbox.token', null);

        $this->service = app(RoutingService::class);
    }

    public function test_haversine_fallback_returns_correct_distance(): void
    {
        // Narvik center to Ankenes (approx 5km)
        $from = new Point(68.43886, 17.42754);
        $to = new Point(68.42010, 17.36520);

        $result = $this->service->route($from, $to, ['transport' => 'car']);

        $this->assertGreaterThan(4.0, $result->distanceKm);
        $this->assertLessThan(6.0, $result->distanceKm);
        $this->assertEquals('internal', $result->provider);
        $this->assertGreaterThan(0, $result->durationMin);
    }

    public function test_fallback_respects_transport_speed(): void
    {
        $from = new Point(68.43886, 17.42754);
        $to = new Point(68.44000, 17.43000); // ~1km

        $carResult = $this->service->route($from, $to, ['transport' => 'car']);
        $walkResult = $this->service->route($from, $to, ['transport' => 'walk']);

        // Walking should take longer
        $this->assertGreaterThan($carResult->durationMin, $walkResult->durationMin);
    }
}

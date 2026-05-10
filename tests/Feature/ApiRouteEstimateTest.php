<?php

namespace Tests\Feature;

use App\Models\GeoZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRouteEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_route_estimate_with_zones(): void
    {
        // Create a test zone
        GeoZone::create([
            'name' => 'Test Zone',
            'slug' => 'test-zone',
            'type' => 'circle',
            'geometry' => [
                'center' => [68.43886, 17.42754],
                'radius_m' => 10000,
            ],
            'is_active' => true,
            'priority' => 100,
        ]);

        $response = $this->postJson('/api/v1/route/estimate', [
            'from' => ['lat' => 68.43886, 'lng' => 17.42754],
            'to' => ['lat' => 68.44000, 'lng' => 17.43000],
            'transport' => 'car',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'distance_km',
                'duration_min',
                'eta',
                'zones' => [
                    '*' => ['id', 'name', 'slug', 'meta'],
                ],
                'provider',
            ]);

        $data = $response->json();
        $this->assertIsFloat($data['distance_km']);
        $this->assertIsInt($data['duration_min']);
    }

    public function test_it_validates_coordinates(): void
    {
        $response = $this->postJson('/api/v1/route/estimate', [
            'from' => ['lat' => 100, 'lng' => 200], // Invalid
            'to' => ['lat' => 68.44000, 'lng' => 17.43000],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from.lat', 'from.lng']);
    }

    public function test_it_includes_price_hint_when_service_type_provided(): void
    {
        $response = $this->postJson('/api/v1/route/estimate', [
            'from' => ['lat' => 68.43886, 'lng' => 17.42754],
            'to' => ['lat' => 68.44000, 'lng' => 17.43000],
            'transport' => 'car',
            'service_type' => 'delivery',
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        // Price hint may be null if pricing rules not set up, but structure should exist
        $this->assertArrayHasKey('price_hint', $data);
    }
}

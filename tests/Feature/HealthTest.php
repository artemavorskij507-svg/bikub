<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'service', 'version']);
    }
}

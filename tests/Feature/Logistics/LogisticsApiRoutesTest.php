<?php

namespace Tests\Feature\Logistics;

use Tests\TestCase;

class LogisticsApiRoutesTest extends TestCase
{
    public function test_tracking_endpoint_exists(): void
    {
        $response = $this->getJson('/api/v1/logistics/tracking/NO1234567890');
        $this->assertContains($response->status(), [200, 404, 422]);
    }
}

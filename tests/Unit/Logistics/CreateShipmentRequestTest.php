<?php

namespace Tests\Unit\Logistics;

use App\Modules\Logistics\Http\Requests\CreateShipmentRequest;
use Tests\TestCase;

class CreateShipmentRequestTest extends TestCase
{
    public function test_rules_are_defined(): void
    {
        $request = new CreateShipmentRequest();
        $this->assertIsArray($request->rules());
        $this->assertArrayHasKey('service_type_id', $request->rules());
    }
}

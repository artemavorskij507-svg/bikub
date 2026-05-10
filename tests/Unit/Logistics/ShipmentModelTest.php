<?php

namespace Tests\Unit\Logistics;

use App\Modules\Logistics\Models\Shipment;
use Tests\TestCase;

class ShipmentModelTest extends TestCase
{
    public function test_shipment_model_has_expected_casts(): void
    {
        $shipment = new Shipment();
        $this->assertArrayHasKey('metadata', $shipment->getCasts());
    }
}

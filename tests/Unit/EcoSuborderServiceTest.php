<?php

namespace Tests\Unit;

use App\Models\DisposalItem;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\ServiceType as ServiceTypeModel;
use App\Models\User;
use App\Services\EcoDisposal\EcoSuborderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcoSuborderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function createParentOrder(string $serviceTypeCode = 'relocation'): Order
    {
        $customer = User::factory()->create();
        $zone = GeoZone::factory()->create();

        return Order::factory()->create([
            'user_id' => $customer->id,
            'geo_zone_id' => $zone->id,
            'metadata' => [
                'service_type' => $serviceTypeCode,
            ],
        ]);
    }

    /** @test */
    public function it_creates_eco_suborder_for_allowed_parent()
    {
        // ServiceType ECO_DISPOSAL must exist
        $ecoType = ServiceTypeModel::factory()->create(['code' => 'eco_disposal']);

        $parent = $this->createParentOrder('relocation');

        $item = DisposalItem::factory()->create([
            'is_active' => true,
        ]);

        $service = app(EcoSuborderService::class);
        $ecoOrder = $service->createEcoSuborderForOrder(
            $parent,
            [['disposal_item_id' => $item->id, 'quantity' => 1]],
            floor: 2,
            hasElevator: true,
            parkingDistanceMeters: 10,
            expressRequested: false,
            addressData: [],
            zoneCode: null
        );

        $this->assertNotEquals($parent->id, $ecoOrder->id);
        $this->assertEquals($parent->id, $ecoOrder->parent_order_id);
        $this->assertEquals('eco_disposal', $ecoOrder->metadata['service_type']);
        $this->assertTrue($parent->subOrders()->whereKey($ecoOrder->id)->exists());
        $this->assertTrue($parent->ecoSubOrders()->whereKey($ecoOrder->id)->exists());
    }
}

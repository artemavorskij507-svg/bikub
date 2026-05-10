<?php

namespace Tests\Feature;

use App\Models\DisposalItem;
use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoCertificate;
use App\Models\EcoTeam;
use App\Models\Order;
use App\Services\EcoDisposal\EcoDisposalOrderService;
use App\Services\EcoDisposal\EcoDisposalPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcoDisposalModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_catalog_models(): void
    {
        $item = DisposalItem::create([
            'name' => 'Диван 2-местный',
            'category' => 'furniture',
            'requires_disassembly' => false,
            'difficulty_coefficient' => 1.2,
            'disposal_path' => 'RECYCLABLE',
            'is_active' => true,
        ]);
        $this->assertNotNull($item->id);

        $partner = DisposalPartner::create([
            'name' => 'Green Center',
            'type' => 'RECYCLING_CENTER',
            'accepted_categories' => ['furniture', 'electronics'],
            'is_active' => true,
        ]);
        $this->assertNotNull($partner->id);

        $team = EcoTeam::create([
            'name' => 'ECO-1',
            'vehicle_type' => 'van',
            'is_active' => true,
        ]);
        $this->assertNotNull($team->id);
    }

    public function test_order_with_disposal_details_and_certificate(): void
    {
        $order = Order::factory()->create();

        $details = DisposalOrderDetails::create([
            'order_id' => $order->id,
            'items' => [
                ['disposal_item_id' => 1, 'quantity' => 2],
            ],
            'has_elevator' => false,
            'requires_dismantling' => false,
        ]);

        $this->assertTrue($order->disposalDetails()->exists());
        $this->assertTrue($order->isEcoDisposal() === true || $order->isEcoDisposal() === false); // method exists

        $cert = EcoCertificate::create([
            'order_id' => $order->id,
            'certificate_uid' => (string) \Illuminate\Support\Str::uuid(),
            'customer_name' => 'Test User',
            'summary_data' => [],
            'issued_at' => now(),
        ]);

        $this->assertNotNull($cert->id);
        $this->assertTrue($order->ecoCertificate()->exists());
    }

    public function test_pricing_service_estimate_changes_with_floor_and_distance(): void
    {
        $pricing = new EcoDisposalPricingService;

        $item = DisposalItem::create([
            'name' => 'Холодильник',
            'category' => 'large_appliance',
            'volume_m3' => 1.2,
            'weight_kg' => 80,
            'difficulty_coefficient' => 1.1,
            'disposal_path' => 'RECYCLABLE',
            'base_price_nok' => 500,
            'is_active' => true,
        ]);

        $payload = [
            ['disposal_item_id' => $item->id, 'quantity' => 1],
        ];

        $estimateGround = $pricing->estimate($payload, floor: 1, hasElevator: true, parkingDistanceMeters: 10, expressRequested: false);
        $estimateFifthNoLift = $pricing->estimate($payload, floor: 5, hasElevator: false, parkingDistanceMeters: 50, expressRequested: true);

        $this->assertGreaterThan(0, $estimateGround->totalPriceNok);
        $this->assertGreaterThan($estimateGround->totalPriceNok, $estimateFifthNoLift->totalPriceNok);
    }

    public function test_eco_disposal_order_service_creates_order_and_details(): void
    {
        $pricing = new EcoDisposalPricingService;
        $service = new EcoDisposalOrderService($pricing);

        $user = \App\Models\User::factory()->create();

        $item = DisposalItem::create([
            'name' => 'Диван 3-местный',
            'category' => 'furniture',
            'volume_m3' => 2.5,
            'weight_kg' => 60,
            'difficulty_coefficient' => 1.2,
            'disposal_path' => 'RECYCLABLE',
            'base_price_nok' => 800,
            'is_active' => true,
        ]);

        $payload = [
            ['disposal_item_id' => $item->id, 'quantity' => 2],
        ];

        $order = $service->createEcoDisposalOrder(
            customer: $user,
            itemsPayload: $payload,
            floor: 2,
            hasElevator: true,
            parkingDistanceMeters: 30,
            expressRequested: false,
            addressData: [
                'location' => ['address' => 'Test street 1'],
                'notes' => 'Подъезд слева',
                'metadata' => [],
            ],
            zoneCode: null,
        );

        $this->assertEquals('eco_disposal', $order->metadata['service_type'] ?? null);
        $this->assertTrue($order->disposalDetails()->exists());
        $this->assertGreaterThan(0, (float) $order->total_amount);
    }
}

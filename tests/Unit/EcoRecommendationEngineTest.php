<?php

namespace Tests\Unit;

use App\Models\DisposalItem;
use App\Models\DisposalOrderDetails;
use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\Order;
use App\Services\EcoDisposal\EcoRecommendationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcoRecommendationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function createEcoOrder(): Order
    {
        return Order::factory()->create([
            'status' => 'pending',
            'metadata' => ['service_type' => 'eco_disposal'],
        ]);
    }

    /** @test */
    public function recommends_partner_matching_category_and_type()
    {
        $order = $this->createEcoOrder();

        $item = DisposalItem::factory()->create([
            'category' => 'hazardous',
            'disposal_path' => 'HAZARDOUS',
        ]);

        DisposalOrderDetails::factory()->create([
            'order_id' => $order->id,
            'items' => [
                ['disposal_item_id' => $item->id, 'quantity' => 1],
            ],
        ]);

        $otherPartner = DisposalPartner::factory()->create([
            'name' => 'Generic',
            'type' => 'RECYCLING_CENTER',
            'accepted_categories' => ['hazardous'],
            'is_active' => true,
        ]);
        $hazPartner = DisposalPartner::factory()->create([
            'name' => 'Haz Processor',
            'type' => 'HAZARDOUS_PROCESSOR',
            'accepted_categories' => ['hazardous'],
            'is_active' => true,
        ]);

        $engine = app(EcoRecommendationEngine::class);
        $partner = $engine->recommendPartnerForOrder($order);

        $this->assertNotNull($partner);
        $this->assertEquals('HAZARDOUS_PROCESSOR', $partner->type);
    }

    /** @test */
    public function recommends_team_with_sufficient_capacity()
    {
        $order = $this->createEcoOrder();
        DisposalOrderDetails::factory()->create([
            'order_id' => $order->id,
            'estimated_volume_m3' => 5,
            'estimated_weight_kg' => 500,
        ]);

        EcoTeam::factory()->create([
            'name' => 'Too Small',
            'vehicle_capacity_m3' => 2,
            'vehicle_max_weight_kg' => 200,
            'is_active' => true,
        ]);
        $okTeam = EcoTeam::factory()->create([
            'name' => 'Medium Van',
            'vehicle_capacity_m3' => 6,
            'vehicle_max_weight_kg' => 800,
            'is_active' => true,
        ]);

        $engine = app(EcoRecommendationEngine::class);
        $team = $engine->recommendTeamForOrder($order);

        $this->assertNotNull($team);
        $this->assertEquals($okTeam->id, $team->id);
    }
}

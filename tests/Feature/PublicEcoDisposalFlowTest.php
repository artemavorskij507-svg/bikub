<?php

namespace Tests\Feature;

use App\Models\DisposalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEcoDisposalFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function eco_disposal_index_is_accessible()
    {
        $this->get(route('eco-disposal.index'))
            ->assertStatus(200)
            ->assertSee('Эко-услуги и утилизация');
    }

    /** @test */
    public function estimate_returns_success_with_valid_payload()
    {
        $item = DisposalItem::factory()->create([
            'name' => 'Стул',
            'category' => 'furniture',
            'disposal_path' => 'RECYCLABLE',
            'is_active' => true,
            'base_price_nok' => 100,
        ]);

        $payload = [
            'items' => [
                ['disposal_item_id' => $item->id, 'quantity' => 2],
            ],
            'floor' => 1,
            'has_elevator' => true,
            'parking_distance_m' => 20,
            'express_requested' => false,
            'zone_code' => 'TEST-ZONE',
        ];

        $res = $this->postJson(route('eco-disposal.estimate'), $payload);
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'estimated_volume_m3',
                    'estimated_weight_kg',
                    'base_price_nok',
                    'difficulty_coefficient',
                    'express_surcharge_nok',
                    'distance_surcharge_nok',
                    'total_price_nok',
                ],
            ]);
    }

    /** @test */
    public function store_creates_order_and_redirects()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = DisposalItem::factory()->create([
            'name' => 'Шкаф',
            'category' => 'furniture',
            'disposal_path' => 'RECYCLABLE',
            'is_active' => true,
            'base_price_nok' => 250,
        ]);

        $payload = [
            'items' => [
                ['disposal_item_id' => $item->id, 'quantity' => 1],
            ],
            'address_line' => 'Main St, 1',
            'postal_code' => '0010',
            'city' => 'Oslo',
            'floor' => 2,
            'has_elevator' => false,
            'parking_distance_m' => 50,
            'express_requested' => false,
        ];

        $res = $this->post(route('eco-disposal.order'), $payload);
        $res->assertRedirect(); // to home or orders.view
    }
}

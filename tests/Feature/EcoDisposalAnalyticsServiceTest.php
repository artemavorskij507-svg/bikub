<?php

namespace Tests\Feature;

use App\Models\DisposalOrderDetails;
use App\Models\EcoCertificate;
use App\Models\Order;
use App\Services\EcoDisposal\EcoDisposalAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcoDisposalAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function summary_counts_basic_metrics()
    {
        $from = now()->subDays(30);
        $to = now();

        $order1 = Order::factory()->create([
            'status' => 'completed',
            'metadata' => ['service_type' => 'eco_disposal'],
            'created_at' => now()->subDays(5),
        ]);
        DisposalOrderDetails::factory()->create([
            'order_id' => $order1->id,
            'estimated_volume_m3' => 1.5,
            'estimated_weight_kg' => 100,
        ]);
        EcoCertificate::factory()->create([
            'order_id' => $order1->id,
            'co2_saved_kg' => 50,
            'items_reused_count' => 3,
            'issued_at' => now()->subDays(4),
        ]);

        $order2 = Order::factory()->create([
            'status' => 'pending',
            'metadata' => ['service_type' => 'eco_disposal'],
            'created_at' => now()->subDays(2),
        ]);

        $service = app(EcoDisposalAnalyticsService::class);
        $summary = $service->getSummary($from, $to);

        $this->assertEquals(2, $summary['total_orders']);
        $this->assertEquals(1, $summary['completed_orders']);
        $this->assertEquals(1.5, $summary['total_volume_m3']);
        $this->assertEquals(100.0, $summary['total_weight_kg']);
        $this->assertEquals(50.0, $summary['total_co2_saved_kg']);
        $this->assertEquals(3, $summary['total_items_reused']);
    }

    /** @test */
    public function dashboard_page_is_accessible_for_authenticated_user()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $this->get(route('filament.pages.eco-disposal-dashboard'))
            ->assertStatus(200)
            ->assertSee('Аналитика ЭКО-услуг');
    }
}

<?php

namespace Tests\Feature;

use App\Models\DisposalItem;
use App\Models\Order;
use App\Models\User;
use App\Services\EcoDisposal\EcoCertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcoCertificateServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function issues_certificate_for_completed_eco_order_and_generates_file()
    {
        Storage::fake(); // memory disk for test
        $order = Order::factory()->create([
            'status' => 'completed',
            'metadata' => ['service_type' => 'eco_disposal', 'address_line' => 'Main 1', 'city' => 'Oslo'],
        ]);
        $user = User::factory()->create(['name' => 'Test User']);
        $order->user()->associate($user)->save();

        $item = DisposalItem::factory()->create([
            'name' => 'Диван',
            'category' => 'furniture',
            'disposal_path' => 'RECYCLABLE',
            'volume_m3' => 1.2,
            'weight_kg' => 80,
            'is_active' => true,
        ]);
        $order->disposalDetails()->create([
            'items' => [
                ['disposal_item_id' => $item->id, 'quantity' => 2],
            ],
            'eco_status' => 'COMPLETED',
        ]);

        $service = app(EcoCertificateService::class);
        $cert = $service->issueForOrder($order);

        $this->assertNotNull($cert->certificate_uid);
        $this->assertNotNull($cert->summary_data);
        $this->assertNotNull($cert->issued_at);
        $this->assertTrue(isset($cert->co2_saved_kg));
        $this->assertTrue($cert->items_reused_count >= 0);
        // File might be HTML fallback if dompdf isn't installed
        $this->assertNotNull($cert->pdf_path);
        Storage::assertExists($cert->pdf_path);
    }

    /** @test */
    public function certificate_is_idempotent()
    {
        Storage::fake();
        $order = Order::factory()->create([
            'status' => 'completed',
            'metadata' => ['service_type' => 'eco_disposal'],
        ]);
        $order->disposalDetails()->create(['items' => [], 'eco_status' => 'COMPLETED']);

        $service = app(EcoCertificateService::class);
        $a = $service->issueForOrder($order);
        $b = $service->issueForOrder($order);
        $this->assertEquals($a->id, $b->id);
    }

    /** @test */
    public function public_route_shows_certificate()
    {
        Storage::fake();
        $order = Order::factory()->create([
            'status' => 'completed',
            'metadata' => ['service_type' => 'eco_disposal'],
        ]);
        $order->disposalDetails()->create(['items' => [], 'eco_status' => 'COMPLETED']);
        $service = app(EcoCertificateService::class);
        $cert = $service->issueForOrder($order);

        $this->get(route('eco-certificate.show', $cert->certificate_uid))
            ->assertStatus(200)
            ->assertSee('Сертификат экологичной утилизации');
    }

    /** @test */
    public function public_route_404_for_unknown_uid()
    {
        $this->get(route('eco-certificate.show', 'unknown-uid'))->assertNotFound();
    }
}

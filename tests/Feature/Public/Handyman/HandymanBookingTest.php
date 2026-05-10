<?php

namespace Tests\Feature\Public\Handyman;

use App\Enums\ServiceType;
use App\Models\HandymanOrderDetails;
use App\Models\HandymanService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandymanBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_handyman_order_from_catalog_service(): void
    {
        $user = User::factory()->create();
        $service = HandymanService::factory()->create([
            'pricing_mode' => HandymanService::PRICING_FIXED,
            'base_rate_minor' => 50000, // 500 NOK
            'slug' => 'test-service',
        ]);

        $response = $this->actingAs($user)->post(route('handyman.service.book', $service->slug), [
            'description' => 'Нужно починить кран',
            'address_line' => 'Test Street 1',
            'postal_code' => '8500',
            'city' => 'Narvik',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'service_type' => ServiceType::HANDYMAN_FIXED->value,
            'status' => 'pending_payment',
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('handyman_order_details', [
            'order_id' => $order->id,
            'handyman_service_id' => $service->id,
            'is_custom_request' => false,
            'description' => 'Нужно починить кран',
            'address_line' => 'Test Street 1',
            'postal_code' => '8500',
            'city' => 'Narvik',
            'estimated_price_minor' => 50000,
        ]);
    }

    public function test_authenticated_user_can_create_custom_handyman_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('handyman.custom.book'), [
            'description' => 'Нужна помощь с нестандартной задачей',
            'address_line' => 'Test Street 2',
            'postal_code' => '8500',
            'city' => 'Narvik',
            'expected_duration_minutes' => 120,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'service_type' => ServiceType::HANDYMAN_HOURLY->value,
            'status' => 'pending_review',
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('handyman_order_details', [
            'order_id' => $order->id,
            'handyman_service_id' => null,
            'is_custom_request' => true,
            'description' => 'Нужна помощь с нестандартной задачей',
        ]);
    }

    public function test_handyman_booking_requires_minimal_address_fields(): void
    {
        $user = User::factory()->create();
        $service = HandymanService::factory()->create([
            'slug' => 'test-service',
        ]);

        $response = $this->actingAs($user)->post(route('handyman.service.book', $service->slug), [
            'description' => 'Test description',
            // Missing address fields
        ]);

        $response->assertSessionHasErrors(['address_line', 'postal_code', 'city']);
    }

    public function test_handyman_booking_estimated_price_is_calculated_based_on_service_pricing_mode(): void
    {
        $user = User::factory()->create();

        // Fixed pricing
        $fixedService = HandymanService::factory()->create([
            'pricing_mode' => HandymanService::PRICING_FIXED,
            'base_rate_minor' => 100000, // 1000 NOK
            'slug' => 'fixed-service',
        ]);

        $response = $this->actingAs($user)->post(route('handyman.service.book', $fixedService), [
            'description' => 'Test',
            'address_line' => 'Test Street',
            'postal_code' => '8500',
            'city' => 'Narvik',
        ]);

        $order = Order::where('user_id', $user->id)->first();
        $details = HandymanOrderDetails::where('order_id', $order->id)->first();

        $this->assertEquals(100000, $details->estimated_price_minor);
        $this->assertEquals(100000, $order->estimated_total);

        // Hourly pricing
        $hourlyService = HandymanService::factory()->create([
            'pricing_mode' => HandymanService::PRICING_HOURLY,
            'base_rate_minor' => 50000, // 500 NOK/hour
            'slug' => 'hourly-service',
        ]);

        $response = $this->actingAs($user)->post(route('handyman.service.book', $hourlyService), [
            'description' => 'Test',
            'address_line' => 'Test Street',
            'postal_code' => '8500',
            'city' => 'Narvik',
            'expected_duration_minutes' => 120, // 2 hours
        ]);

        $order = Order::where('user_id', $user->id)->orderBy('id', 'desc')->first();
        $details = HandymanOrderDetails::where('order_id', $order->id)->first();

        // 500 * 2 = 1000 NOK
        $this->assertEquals(100000, $details->estimated_price_minor);
    }
}

<?php

namespace Tests\Unit;

use App\Models\ErrandOrderDetails;
use App\Services\Errand\ErrandPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrandPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ErrandPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ErrandPricingService;
    }

    /** @test */
    public function it_calculates_basic_price_without_urgency_and_trusted_helper()
    {
        // Упростим конфиг, чтобы тест был предсказуем
        config()->set('errand.pricing', [
            'base_fee' => 1000,
            'distance_fee_per_km' => 500,
            'time_fee_per_minute' => 100,
            'complexity_multipliers' => [
                1 => 1.0,
                2 => 1.2,
            ],
            'trusted_helper_fee' => 2000,
            'urgency_multiplier' => 1.5,
            'min_total_estimated_price' => 0,
            'max_total_estimated_price' => 0,
        ]);

        $details = ErrandOrderDetails::factory()->make([
            'expected_duration_minutes' => 30,
            'complexity_level' => 2,
            'is_urgent' => false,
            'requires_trusted_helper' => false,
            'material_advance_amount' => 0,
        ]);

        $distanceKm = 10.0;

        $result = $this->service->estimate($details, $distanceKm);

        // Проверим компоненты:
        // base = 1000
        // distance = 500 * 10 = 5000
        // time = 100 * 30 = 3000
        // subtotal = 1000 + 5000 + 3000 = 9000
        // complexity (2 => 1.2) => +20% от subtotal = 1800
        // итого без urgency/trusted = 9000 + 1800 = 10800

        $this->assertSame(1000, $result['base_fee']);
        $this->assertSame(5000, $result['distance_fee']);
        $this->assertSame(3000, $result['time_fee']);
        $this->assertSame(1800, $result['complexity_fee']);
        $this->assertSame(0, $result['trusted_helper_fee']);
        $this->assertSame(0, $result['urgency_fee']);
        $this->assertSame(0, $result['material_advance_amount']);
        $this->assertSame(10800, $result['total_estimated_price']);
    }

    /** @test */
    public function it_applies_urgency_and_trusted_helper_and_material_advance()
    {
        config()->set('errand.pricing', [
            'base_fee' => 1000,
            'distance_fee_per_km' => 0,
            'time_fee_per_minute' => 0,
            'complexity_multipliers' => [
                1 => 1.0,
            ],
            'trusted_helper_fee' => 5000,
            'urgency_multiplier' => 1.5,
            'min_total_estimated_price' => 0,
            'max_total_estimated_price' => 0,
        ]);

        $details = ErrandOrderDetails::factory()->make([
            'expected_duration_minutes' => 0,
            'complexity_level' => 1,
            'is_urgent' => true,
            'requires_trusted_helper' => true,
            'material_advance_amount' => 20000,
        ]);

        // subtotal = 1000
        // complexity = 0
        // urgency: (subtotal + complexity) * 0.5 = 500
        // trusted_helper = 5000
        // material_advance = 20000
        // total = 1000 + 0 + 0 + 0 + 5000 + 500 + 20000 = 26500

        $result = $this->service->estimate($details, 0);

        $this->assertSame(1000, $result['base_fee']);
        $this->assertSame(0, $result['distance_fee']);
        $this->assertSame(0, $result['time_fee']);
        $this->assertSame(0, $result['complexity_fee']);
        $this->assertSame(5000, $result['trusted_helper_fee']);
        $this->assertSame(500, $result['urgency_fee']);
        $this->assertSame(20000, $result['material_advance_amount']);
        $this->assertSame(26500, $result['total_estimated_price']);
    }

    /** @test */
    public function it_can_fill_errand_details_fields()
    {
        config()->set('errand.pricing', [
            'base_fee' => 1000,
            'distance_fee_per_km' => 1000,
            'time_fee_per_minute' => 0,
            'complexity_multipliers' => [1 => 1.0],
            'trusted_helper_fee' => 0,
            'urgency_multiplier' => 1.0,
            'min_total_estimated_price' => 0,
            'max_total_estimated_price' => 0,
        ]);

        $details = ErrandOrderDetails::factory()->make([
            'expected_duration_minutes' => 0,
            'complexity_level' => 1,
            'is_urgent' => false,
            'requires_trusted_helper' => false,
            'material_advance_amount' => 0,
        ]);

        $this->service->estimateAndFill($details, 2.5);

        $this->assertSame(1000, $details->base_fee);
        $this->assertSame(2500, $details->distance_fee);
        $this->assertSame(0, $details->time_fee);
        $this->assertSame(0, $details->complexity_fee);
        $this->assertSame(0, $details->trusted_helper_fee);
        $this->assertSame(3500, $details->total_estimated_price);
    }

    /** @test */
    public function it_throws_exception_for_negative_distance()
    {
        $details = ErrandOrderDetails::factory()->make();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Distance cannot be negative.');

        $this->service->estimate($details, -5.0);
    }

    /** @test */
    public function it_respects_min_and_max_total_estimated_price()
    {
        config()->set('errand.pricing', [
            'base_fee' => 100,
            'distance_fee_per_km' => 0,
            'time_fee_per_minute' => 0,
            'complexity_multipliers' => [1 => 1.0],
            'trusted_helper_fee' => 0,
            'urgency_multiplier' => 1.0,
            'min_total_estimated_price' => 5000,
            'max_total_estimated_price' => 10000,
        ]);

        $details = ErrandOrderDetails::factory()->make([
            'expected_duration_minutes' => 0,
            'complexity_level' => 1,
            'is_urgent' => false,
            'requires_trusted_helper' => false,
            'material_advance_amount' => 0,
        ]);

        // Сумма будет 100, но минимум 5000
        $result = $this->service->estimate($details, 0);
        $this->assertSame(5000, $result['total_estimated_price']);

        // Тест максимума - установим очень большую сумму через material_advance
        $details->material_advance_amount = 50000;
        $result = $this->service->estimate($details, 0);
        $this->assertSame(10000, $result['total_estimated_price']);
    }
}

<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Listeners\ApplyLoyaltyAndPromocodes;
use App\Listeners\LogOrderActivity;
use App\Listeners\ProcessOrderPayment;
use App\Models\Order;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderPlacedEventTest extends TestCase
{
    /**
     * Тест: OrderPlaced подія диспетчується при створенні замовлення
     */
    public function test_order_placed_event_is_dispatched()
    {
        Event::fake([OrderPlaced::class]);

        // Створити замовлення
        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'coupon_code' => 'TEST10',
            'points_to_redeem' => 50,
        ]);

        // Перевірити, що подія була диспетчена
        Event::assertDispatched(OrderPlaced::class, 1);
    }

    /**
     * Тест: Усі слухачі зареєстровані для OrderPlaced
     */
    public function test_all_listeners_are_registered()
    {
        $listeners = config('events.listen.App\Events\OrderPlaced')
            ?? config('events.App\Events\OrderPlaced');

        $this->assertContains(ProcessOrderPayment::class, $listeners);
        $this->assertContains(ApplyLoyaltyAndPromocodes::class, $listeners);
        $this->assertContains(LogOrderActivity::class, $listeners);
    }

    /**
     * Тест: Order модель містить нові поля
     */
    public function test_order_model_has_new_fields()
    {
        $order = Order::factory()->create();

        // Перевірити, що поля існують у $fillable
        $this->assertContains('final_price', $order->getFillable());
        $this->assertContains('discount_amount', $order->getFillable());
        $this->assertContains('coupon_code', $order->getFillable());
        $this->assertContains('points_to_redeem', $order->getFillable());
    }

    /**
     * Тест: Order модель має правильні типи даних
     */
    public function test_order_model_has_correct_casts()
    {
        $order = Order::factory()->create();
        $casts = $order->getCasts();

        $this->assertEquals('decimal:2', $casts['final_price'] ?? null);
        $this->assertEquals('decimal:2', $casts['discount_amount'] ?? null);
        $this->assertEquals('integer', $casts['points_to_redeem'] ?? null);
    }

    /**
     * Тест: OrderPlaced подія містить Order об'єкт
     */
    public function test_order_placed_event_carries_order()
    {
        $order = Order::factory()->create();
        $event = new OrderPlaced($order);

        $this->assertInstanceOf(Order::class, $event->order);
        $this->assertEquals($order->id, $event->order->id);
    }
}

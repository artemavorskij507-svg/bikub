<?php

namespace Tests\Unit\Presenters;

use App\Enums\ServiceType;
use App\Models\Order;
use App\Presenters\OrderPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_formats_eco_disposal_orders(): void
    {
        $order = Order::factory()->create([
            'service_type' => ServiceType::ECO_DISPOSAL->value,
        ]);

        $card = OrderPresenter::forAccount($order);

        $this->assertSame('Эко-вывоз', $card['title']);
        $this->assertSame(ServiceType::ECO_DISPOSAL->value, $card['service_type']);
    }
}

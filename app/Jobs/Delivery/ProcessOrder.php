<?php

namespace App\Jobs\Delivery;

use App\Enums\DeliveryTrackingStatus;
use App\Events\Delivery\OrderCreated;
use App\Models\Delivery\DeliveryOrder;
use App\Services\Delivery\CourierSelectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DeliveryOrder $deliveryOrder
    ) {
        $this->onQueue('delivery');
    }

    /**
     * Execute the job.
     */
    public function handle(CourierSelectorService $courierSelector): void
    {
        try {
            $this->deliveryOrder->order->update([
                'status' => 'confirmed',
            ]);

            $this->deliveryOrder->update([
                'tracking_status' => DeliveryTrackingStatus::PENDING,
            ]);

            if (
                Config::get('delivery.auto_assign_courier', false)
                && ! $this->deliveryOrder->courier_id
            ) {
                $this->autoAssignCourier($courierSelector);
            }

            if ($this->deliveryOrder->type->value === 'grocery' && $this->deliveryOrder->orderable) {
                $this->deliveryOrder->orderable->suggestSubstitutions();
            }

            event(new OrderCreated($this->deliveryOrder));

            Log::info('Delivery order processed', [
                'delivery_order_id' => $this->deliveryOrder->id,
                'order_id' => $this->deliveryOrder->order_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process delivery order', [
                'delivery_order_id' => $this->deliveryOrder->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function autoAssignCourier(CourierSelectorService $courierSelector): void
    {
        $courier = $courierSelector->findForDelivery($this->deliveryOrder);

        if (! $courier) {
            return;
        }

        $this->deliveryOrder->courier_id = $courier->id;
        $this->deliveryOrder->tracking_status = DeliveryTrackingStatus::ASSIGNED;
        $this->deliveryOrder->save();
    }
}

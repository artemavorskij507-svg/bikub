<?php

namespace App\Services\EcoDisposal;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EcoSuborderService
{
    public function __construct(
        protected EcoDisposalOrderService $ecoOrderService,
    ) {}

    /**
     * Create ECO_DISPOSAL sub-order for a parent order (relocation / bulky / handyman / etc.).
     *
     * @param  array<int, array{disposal_item_id:int, quantity:int}>  $itemsPayload
     * @param  array<string,mixed>  $addressData
     */
    public function createEcoSuborderForOrder(
        Order $parentOrder,
        array $itemsPayload,
        ?int $floor,
        bool $hasElevator,
        ?int $parkingDistanceMeters,
        bool $expressRequested,
        array $addressData,
        ?string $zoneCode = null
    ): Order {
        if (! $this->isAllowedParent($parentOrder)) {
            throw new InvalidArgumentException('Этот тип заказа не поддерживает ЭКО-подзаказы.');
        }

        /** @var User $customer */
        $customer = $parentOrder->user;
        if (! $customer) {
            throw new InvalidArgumentException('У родительского заказа отсутствует клиент.');
        }

        // Наследуем адрес/метаданные, если не переданы явно
        $addressData = array_merge([
            'location' => $parentOrder->location,
            'notes' => $parentOrder->notes,
            'metadata' => $parentOrder->metadata ?? [],
        ], $addressData);

        $ecoOrder = $this->ecoOrderService->createEcoDisposalOrder(
            $customer,
            $itemsPayload,
            $floor,
            $hasElevator,
            $parkingDistanceMeters,
            $expressRequested,
            $addressData,
            $zoneCode
        );

        $ecoOrder->parent_order_id = $parentOrder->id;
        $ecoOrder->save();

        Log::info('Eco sub-order created', [
            'parent_order_id' => $parentOrder->id,
            'eco_order_id' => $ecoOrder->id,
        ]);

        return $ecoOrder;
    }

    protected function isAllowedParent(Order $order): bool
    {
        $serviceType = $order->metadata['service_type'] ?? null;
        if (! $serviceType && $order->orderItems()->exists()) {
            $serviceType = optional($order->orderItems()->first()->serviceType)->code;
        }

        // TODO: синхронизировать с реальными кодами сервисов в проекте
        $allowed = [
            'relocation',
            'bulky_delivery',
            'handyman',
            'social_care',
            'errand',
        ];

        return $serviceType && in_array($serviceType, $allowed, true);
    }
}

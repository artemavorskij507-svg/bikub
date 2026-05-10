<?php

namespace App\Services\SocialCare;

use App\Enums\ServiceType;
use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\OrderCareContext;
use App\Models\TrustedContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialCareIntegrationService
{
    public function __construct(
        private CareOrderService $careOrderService
    ) {}

    /**
     * Ensure care context exists for an order.
     */
    public function ensureCareContextForOrder(
        Order $order,
        ClientProfile $client,
        ?TrustedContact $trustedContact = null,
        ?User $initiator = null,
        ?string $notesForPerformer = null
    ): OrderCareContext {
        return DB::transaction(function () use ($order, $client, $trustedContact, $initiator, $notesForPerformer) {
            $context = OrderCareContext::firstOrNew(['order_id' => $order->id]);

            if (! $context->exists) {
                $context->fill([
                    'client_profile_id' => $client->id,
                    'trusted_contact_id' => $trustedContact?->id,
                    'is_vulnerable_client' => true,
                    'needs_extra_care' => true,
                    'notes_for_performer' => $notesForPerformer ?? $client->mobility_notes,
                    'created_by_user_id' => $initiator?->id ?? auth()->id(),
                ]);
                $context->save();

                Log::info('Care context created for order', [
                    'order_id' => $order->id,
                    'client_profile_id' => $client->id,
                ]);
            }

            return $context;
        });
    }

    /**
     * Attach a Social Care visit as a sub-order to an existing order.
     */
    public function attachSocialVisitToOrder(
        Order $parentOrder,
        ClientProfile $client,
        ?TrustedContact $trustedContact,
        CareService $careService,
        Carbon $scheduledStartAt,
        ?int $durationMinutes = null,
        ?User $initiator = null,
        ?string $notesForHelper = null
    ): Order {
        return DB::transaction(function () use (
            $parentOrder,
            $client,
            $trustedContact,
            $careService,
            $scheduledStartAt,
            $durationMinutes,
            $initiator,
            $notesForHelper
        ) {
            // Ensure care context exists for parent order
            $this->ensureCareContextForOrder($parentOrder, $client, $trustedContact, $initiator);

            // Create the social care order
            $socialCareOrder = Order::create([
                'user_id' => $parentOrder->user_id,
                'parent_order_id' => $parentOrder->id,
                'status' => 'pending',
                'geo_zone_id' => $parentOrder->geo_zone_id,
                'address_id' => $parentOrder->address_id,
                'scheduled_at' => $scheduledStartAt,
                'metadata' => [
                    'service_type' => 'social_care_visit',
                    'parent_order_id' => $parentOrder->id,
                    'parent_order_number' => $parentOrder->order_number,
                ],
            ]);

            // Create care order details
            $scheduledEndAt = $durationMinutes
                ? $scheduledStartAt->copy()->addMinutes($durationMinutes)
                : $scheduledStartAt->copy()->addMinutes($careService->base_duration_minutes ?? 60);

            $careDetails = CareOrderDetails::create([
                'order_id' => $socialCareOrder->id,
                'client_profile_id' => $client->id,
                'trusted_contact_id' => $trustedContact?->id,
                'care_service_id' => $careService->id,
                'scheduled_start_at' => $scheduledStartAt,
                'scheduled_end_at' => $scheduledEndAt,
                'care_status' => 'SCHEDULED',
                'notes_for_helper' => $notesForHelper ?? "Связано с заказом #{$parentOrder->order_number}",
            ]);

            Log::info('Social care visit attached to order', [
                'parent_order_id' => $parentOrder->id,
                'social_care_order_id' => $socialCareOrder->id,
                'care_service_id' => $careService->id,
            ]);

            // Dispatch event
            event(new CareOrderCreated($socialCareOrder, $careDetails, $initiator));

            return $socialCareOrder->fresh(['careDetails']);
        });
    }

    /**
     * Create a sub-order from a Social Care order.
     *
     * @param  ServiceType  $subOrderServiceType  The type of sub-order to create (ECO_DISPOSAL, etc.)
     * @param  array  $payload  Additional data for the sub-order
     */
    public function createSubOrderFromSocialCare(
        Order $socialCareOrder,
        ServiceType $subOrderServiceType,
        array $payload,
        ?User $initiator = null
    ): Order {
        return DB::transaction(function () use ($socialCareOrder, $subOrderServiceType, $payload, $initiator) {
            // Verify this is a social care order
            if (! $socialCareOrder->isSocialCare()) {
                throw new \InvalidArgumentException('Order must be a social care visit');
            }

            $careDetails = $socialCareOrder->careDetails;
            if (! $careDetails || ! $careDetails->clientProfile) {
                throw new \InvalidArgumentException('Social care order must have client profile');
            }

            $client = $careDetails->clientProfile;

            // Create the sub-order based on service type
            $subOrder = match ($subOrderServiceType) {
                ServiceType::ECO_DISPOSAL => $this->createEcoSubOrder($socialCareOrder, $client, $payload, $initiator),
                // TODO: Add other service types as needed
                // ServiceType::HANDYMAN => $this->createHandymanSubOrder(...),
                // ServiceType::GROCERY_DELIVERY => $this->createDeliverySubOrder(...),
                default => throw new \InvalidArgumentException("Service type {$subOrderServiceType->value} not yet implemented"),
            };

            Log::info('Sub-order created from social care', [
                'social_care_order_id' => $socialCareOrder->id,
                'sub_order_id' => $subOrder->id,
                'service_type' => $subOrderServiceType->value,
            ]);

            return $subOrder;
        });
    }

    /**
     * Create an eco-disposal sub-order from social care.
     */
    protected function createEcoSubOrder(
        Order $socialCareOrder,
        ClientProfile $client,
        array $payload,
        ?User $initiator
    ): Order {
        // Check if EcoDisposalOrderService exists
        if (! class_exists(\App\Services\EcoDisposal\EcoDisposalOrderService::class)) {
            throw new \RuntimeException('EcoDisposalOrderService not available');
        }

        $ecoService = app(\App\Services\EcoDisposal\EcoDisposalOrderService::class);

        // Create eco order with parent reference
        $ecoOrder = Order::create([
            'user_id' => $socialCareOrder->user_id,
            'parent_order_id' => $socialCareOrder->id,
            'status' => 'pending',
            'geo_zone_id' => $socialCareOrder->geo_zone_id,
            'address_id' => $socialCareOrder->address_id,
            'metadata' => [
                'service_type' => 'eco_disposal',
                'parent_order_id' => $socialCareOrder->id,
                'parent_order_number' => $socialCareOrder->order_number,
                'created_from_social_care' => true,
                'client_profile_id' => $client->id,
            ],
        ]);

        // TODO: Call EcoDisposalOrderService to create disposal details
        // For now, just create the order structure
        // $ecoService->createEcoDisposalOrder(...);

        return $ecoOrder;
    }

    /**
     * Check if an order has care context.
     */
    public function hasCareContext(Order $order): bool
    {
        return $order->careContext()->exists();
    }

    /**
     * Get care context for an order.
     */
    public function getCareContext(Order $order): ?OrderCareContext
    {
        return $order->careContext;
    }
}

<?php

namespace App\Services\EcoDisposal;

use App\Enums\ServiceType;
use App\Events\OrderCreated;
use App\Models\DisposalOrderDetails;
use App\Models\GeoZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ServiceType as ServiceTypeModel;
use App\Models\User;
use App\Services\EcoDisposal\Contracts\EcoRecommendationEngineInterface;
use App\Services\FeatureFlags\Context;
use App\Services\FeatureFlags\FeatureFlagger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcoDisposalOrderService
{
    public function __construct(
        protected EcoDisposalPricingService $pricingService,
        protected EcoRecommendationEngineInterface $recommendationEngine,
        protected FeatureFlagger $featureFlagger,
    ) {}

    /**
     * Create ECO_DISPOSAL order with details.
     *
     * @param  array<int, array{disposal_item_id:int, quantity:int}>  $itemsPayload
     * @param  array<string,mixed>  $addressData
     */
    public function createEcoDisposalOrder(
        User $customer,
        array $itemsPayload,
        ?int $floor,
        bool $hasElevator,
        ?int $parkingDistanceMeters,
        bool $expressRequested,
        array $addressData,
        ?string $zoneCode = null
    ): Order {
        return DB::transaction(function () use (
            $customer,
            $itemsPayload,
            $floor,
            $hasElevator,
            $parkingDistanceMeters,
            $expressRequested,
            $addressData,
            $zoneCode
        ) {
            // basic validation
            if (empty($itemsPayload)) {
                throw new \InvalidArgumentException('At least one disposal item is required.');
            }

            $estimate = $this->pricingService->estimate(
                $itemsPayload,
                $floor,
                $hasElevator,
                $parkingDistanceMeters,
                $expressRequested,
                $zoneCode
            );

            // find ECO_DISPOSAL ServiceType (by code)
            $serviceType = ServiceTypeModel::where('code', ServiceType::ECO_DISPOSAL->value)->first();

            if (! $serviceType) {
                throw new \RuntimeException('ServiceType ECO_DISPOSAL not configured.');
            }

            $geoZone = null;
            if ($zoneCode) {
                $geoZone = GeoZone::where('code', $zoneCode)->orWhere('slug', $zoneCode)->first();
            }

            // create base Order
            $order = Order::create([
                'user_id' => $customer->id,
                'status' => 'pending', // TODO: consider dedicated PENDING_PAYMENT if needed
                'geo_zone_id' => $geoZone?->id,
                'location' => $addressData['location'] ?? null,
                'notes' => $addressData['notes'] ?? null,
                'total_amount' => $estimate->totalPriceNok,
                'currency' => 'NOK',
                'payment_status' => 'pending',
                'metadata' => array_merge($addressData['metadata'] ?? [], [
                    'service_type' => ServiceType::ECO_DISPOSAL->value,
                    'eco' => [
                        'estimated_volume_m3' => $estimate->estimatedVolumeM3,
                        'estimated_weight_kg' => $estimate->estimatedWeightKg,
                    ],
                ]),
            ]);

            // create single OrderItem for ECO_DISPOSAL
            OrderItem::create([
                'order_id' => $order->id,
                'service_type_id' => $serviceType->id,
                'name' => $serviceType->name,
                'description' => $addressData['description'] ?? 'Эко-услуги и утилизация',
                'quantity' => 1,
                'unit_price' => $estimate->totalPriceNok,
                'total_price' => $estimate->totalPriceNok,
            ]);

            // create DisposalOrderDetails
            $details = DisposalOrderDetails::create([
                'order_id' => $order->id,
                'items' => $itemsPayload,
                'floor' => $floor,
                'has_elevator' => $hasElevator,
                'parking_distance_m' => $parkingDistanceMeters,
                'requires_dismantling' => (bool) ($addressData['requires_dismantling'] ?? false),
                'express_requested' => $expressRequested,
                'estimated_volume_m3' => $estimate->estimatedVolumeM3,
                'estimated_weight_kg' => $estimate->estimatedWeightKg,
                'estimated_price_nok' => $estimate->totalPriceNok,
                'eco_partner_hint_id' => $addressData['eco_partner_hint_id'] ?? null,
            ]);

            // Рекомендации партнёра и команды (rule-based v1, управляется feature flags)
            $ctx = new Context(
                orgId: $customer->org_id ?? null,
                zoneId: $order->geo_zone_id,
                serviceTypeId: $serviceType->id,
                userId: $customer->id,
                role: method_exists($customer, 'getRoleNames') ? $customer->getRoleNames()->first() : null,
            );
            if ($this->featureFlagger->enabled('eco_recommendations_enabled', $ctx)) {
                $recommendedPartner = null;
                $recommendedTeam = null;
                try {
                    $recommendedPartner = $this->recommendationEngine->recommendPartnerForOrder($order);
                    $recommendedTeam = $this->recommendationEngine->recommendTeamForOrder($order);
                } catch (\Throwable $e) {
                    Log::warning('Eco recommendations failed', ['order_id' => $order->id, 'err' => $e->getMessage()]);
                }

                $meta = $order->metadata ?? [];
                $meta['eco']['recommended_partner_id'] = $recommendedPartner?->id;
                $meta['eco']['recommended_team_id'] = $recommendedTeam?->id;
                $order->metadata = $meta;
                $order->save();

                app(\App\Services\EcoDisposal\EcoRecommendationLogger::class)->logEngineOutput(
                    $order,
                    features: [
                        'zone_code' => $order->metadata['zone_code'] ?? null,
                        'estimated_volume_m3' => $estimate->estimatedVolumeM3,
                        'estimated_weight_kg' => $estimate->estimatedWeightKg,
                    ],
                    recommendations: [
                        'partner_id' => $recommendedPartner?->id,
                        'team_id' => $recommendedTeam?->id,
                    ]
                );

                // По флагам — либо сразу применяем, либо оставляем как подсказку
                if ($recommendedPartner && $this->featureFlagger->enabled('eco_auto_select_partner', $ctx)) {
                    $details->eco_partner_id = $recommendedPartner->id;
                }
                if ($recommendedTeam && $this->featureFlagger->enabled('eco_auto_assign_team', $ctx)) {
                    $details->eco_team_id = $recommendedTeam->id;
                }
                $details->save();
            }

            event(new OrderCreated($order));

            Log::info('Eco disposal order created', [
                'order_id' => $order->id,
                'user_id' => $customer->id,
                'service_type' => ServiceType::ECO_DISPOSAL->value,
            ]);

            return $order;
        });
    }
}

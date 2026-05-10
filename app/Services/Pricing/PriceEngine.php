<?php

namespace App\Services\Pricing;

use App\Models\PricingRule;
use App\Services\Pricing\Support\PriceMath;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PriceEngine
{
    public function __construct(
        protected CacheRepository $cache,
        protected DemandService $demandService,
    ) {}

    public function estimate(OrderContext $context, ?Collection $rules = null): PriceEstimateResult
    {
        $startedAt = microtime(true);

        $rules = $rules ?? $this->getActiveRules();
        $subtotal = 0.0;
        $breakdown = [];
        $currency = 'NOK';

        foreach ($rules as $rule) {
            if (! $this->ruleApplies($rule, $context)) {
                continue;
            }

            $amount = $this->calculateAmount($rule, $context, $subtotal);

            if ($amount === 0.0) {
                continue;
            }

            $subtotal = PriceMath::round($subtotal + $amount);

            $breakdown[] = [
                'rule_id' => $rule->getKey(),
                'rule_name' => $rule->name,
                'type' => $rule->type,
                'amount' => $amount,
            ];
        }

        $total = $subtotal;

        if ($context->zone) {
            $multiplier = $this->demandService->getMultiplier($context->zone);
            if ($multiplier !== 1.0) {
                $extra = PriceMath::round($subtotal * ($multiplier - 1));
                if ($extra !== 0.0) {
                    $total = PriceMath::round($total + $extra);
                    $breakdown[] = [
                        'rule_id' => null,
                        'rule_name' => 'Demand multiplier',
                        'type' => 'demand_multiplier',
                        'amount' => $extra,
                    ];
                }
            }
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($durationMs > 200) {
            Log::warning('price_estimate.slow', [
                'service_type' => $context->serviceType,
                'zone' => $context->zone,
                'duration_ms' => $durationMs,
            ]);

            if (app()->bound('sentry')) {
                app('sentry')->captureMessage(sprintf(
                    'Slow price estimate for %s (%dms)',
                    $context->serviceType,
                    $durationMs
                ));
            }
        }

        return new PriceEstimateResult($subtotal, $total, $currency, $breakdown, $durationMs);
    }

    public function getActiveRules(): Collection
    {
        $ttl = (int) config('pricing.cache_ttl', 60);

        return $this->cache->remember('pricing:active_rules', $ttl, function () {
            return PricingRule::query()
                ->active()
                ->orderBy('priority')
                ->get();
        });
    }

    /**
     * @param  \App\Models\PricingRule  $rule
     */
    protected function ruleApplies($rule, OrderContext $context): bool
    {
        if ($rule->service_type && $rule->service_type !== $context->serviceType) {
            return false;
        }

        $applies = $rule->applies_to ?? [];

        if (isset($applies['service_types']) && ! in_array($context->serviceType, $applies['service_types'], true)) {
            return false;
        }

        if (isset($applies['categories'], $context->category) && ! in_array($context->category, $applies['categories'], true)) {
            return false;
        }

        if (isset($applies['zones'], $context->zone) && ! in_array($context->zone, $applies['zones'], true)) {
            return false;
        }

        return $this->conditionsSatisfied($rule, $context);
    }

    protected function conditionsSatisfied(PricingRule $rule, OrderContext $context): bool
    {
        $conditions = $rule->conditions ?? [];

        if (isset($conditions['min_weight']) && $context->totalWeightKg() < (float) $conditions['min_weight']) {
            return false;
        }

        if (isset($conditions['max_weight']) && $context->totalWeightKg() > (float) $conditions['max_weight']) {
            return false;
        }

        if (isset($conditions['hours'], $context->scheduledAt) && is_array($conditions['hours']) && count($conditions['hours']) >= 2) {
            [$from, $to] = $conditions['hours'];
            $hour = (int) $context->scheduledAt->format('H');
            if ($hour < (int) $from || $hour >= (int) $to) {
                return false;
            }
        }

        if (isset($conditions['min_volume_m3']) && $context->totalVolumeM3() < (float) $conditions['min_volume_m3']) {
            return false;
        }

        if (isset($conditions['max_distance_km']) && $context->distanceKm !== null && $context->distanceKm > (float) $conditions['max_distance_km']) {
            return false;
        }

        return true;
    }

    protected function calculateAmount(PricingRule $rule, OrderContext $context, float $currentSubtotal): float
    {
        $value = (float) ($rule->value ?? 0);

        return match ($rule->type) {
            'base_fee' => $value,
            'flat' => $value,
            'distance' => $context->distanceKm ? $value * $context->distanceKm : 0.0,
            'weight_surcharge' => $this->weightSurcharge($rule, $context),
            'time_multiplier' => $this->percentage($value, $currentSubtotal),
            'percentage' => $this->percentage($value, $currentSubtotal),
            'service_specific' => $value,
            'demand_multiplier' => 0.0,
            default => 0.0,
        };
    }

    protected function percentage(float $value, float $base): float
    {
        if ($value === 0.0 || $base === 0.0) {
            return 0.0;
        }

        return PriceMath::round($base * ($value / 100));
    }

    protected function weightSurcharge(PricingRule $rule, OrderContext $context): float
    {
        $meta = $rule->meta ?? [];
        $threshold = (float) ($meta['min_weight_kg'] ?? 0);
        $perKg = (bool) ($meta['per_kg'] ?? false);
        $weight = $context->totalWeightKg();

        if ($weight <= $threshold) {
            return 0.0;
        }

        $over = $weight - $threshold;
        $value = (float) ($rule->value ?? 0);

        return $perKg
            ? PriceMath::round($over * $value)
            : $value;
    }
}

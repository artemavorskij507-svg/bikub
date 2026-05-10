<?php

namespace App\Services;

use App\Models\AbAssignment;
use App\Models\AbExperiment;
use App\Models\PricingCalculationLog;
use App\Models\PricingContextRule;
use App\Models\ServiceType;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DynamicPricingService
{
    private array $contextData = [];

    private array $appliedRules = [];

    private array $calculationSteps = [];

    public function calculatePrice(ServiceType $serviceType, array $context): array
    {
        $this->contextData = $context;
        $this->appliedRules = [];
        $this->calculationSteps = [];

        $basePrice = $serviceType->base_price;
        $surgeMultiplier = 1.0;
        $discountAmount = 0;

        // Step 1: Apply A/B experiments
        $abResult = $this->applyAbExperiments($serviceType);
        if ($abResult) {
            $surgeMultiplier *= $abResult['surge_multiplier'] ?? 1.0;
            $discountAmount += $abResult['discount_amount'] ?? 0;
        }

        // Step 2: Apply context-based pricing rules
        $contextResult = $this->applyContextRules($serviceType);
        if ($contextResult) {
            $surgeMultiplier *= $contextResult['surge_multiplier'] ?? 1.0;
            $discountAmount += $contextResult['discount_amount'] ?? 0;
        }

        // Step 3: Apply weather-based pricing
        $weatherResult = $this->applyWeatherPricing($serviceType);
        if ($weatherResult) {
            $surgeMultiplier *= $weatherResult['surge_multiplier'] ?? 1.0;
        }

        // Step 4: Apply time-based pricing
        $timeResult = $this->applyTimeBasedPricing($serviceType);
        if ($timeResult) {
            $surgeMultiplier *= $timeResult['surge_multiplier'] ?? 1.0;
        }

        // Step 5: Apply slot overload pricing
        $slotResult = $this->applySlotOverloadPricing($serviceType);
        if ($slotResult) {
            $surgeMultiplier *= $slotResult['surge_multiplier'] ?? 1.0;
        }

        // Calculate final price
        $surgePrice = $basePrice * $surgeMultiplier;
        $finalPrice = max(0, $surgePrice - $discountAmount);

        $result = [
            'base_price' => $basePrice,
            'surge_multiplier' => $surgeMultiplier,
            'surge_price' => $surgePrice,
            'discount_amount' => $discountAmount,
            'total_price' => $finalPrice,
            'applied_rules' => $this->appliedRules,
            'calculation_steps' => $this->calculationSteps,
            'estimated_delivery' => $this->calculateEstimatedDelivery($serviceType, $context),
        ];

        // Log calculation
        $this->logCalculation($serviceType, $result);

        return $result;
    }

    private function applyAbExperiments(ServiceType $serviceType): ?array
    {
        $experiments = AbExperiment::where('status', 'running')
            ->where('started_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>', now());
            })
            ->get();

        foreach ($experiments as $experiment) {
            if ($this->isExperimentApplicable($experiment, $serviceType)) {
                $variant = $this->getExperimentVariant($experiment);
                if ($variant) {
                    $this->appliedRules[] = [
                        'type' => 'ab_experiment',
                        'experiment_id' => $experiment->id,
                        'variant' => $variant,
                        'impact' => $variant['pricing_impact'] ?? [],
                    ];

                    $this->calculationSteps[] = [
                        'step' => 'A/B Experiment',
                        'experiment' => $experiment->name,
                        'variant' => $variant['name'],
                        'impact' => $variant['pricing_impact'] ?? [],
                    ];

                    return $variant['pricing_impact'] ?? [];
                }
            }
        }

        return null;
    }

    private function applyContextRules(ServiceType $serviceType): ?array
    {
        $rules = PricingContextRule::where('active', true)
            ->where(function ($query) use ($serviceType) {
                $query->whereNull('service_type_id')
                    ->orWhere('service_type_id', $serviceType->id);
            })
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            })
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateRuleConditions($rule)) {
                $actions = $this->executeRuleActions($rule);

                $this->appliedRules[] = [
                    'type' => 'context_rule',
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'conditions' => $rule->conditions,
                    'actions' => $actions,
                ];

                $this->calculationSteps[] = [
                    'step' => 'Context Rule',
                    'rule' => $rule->name,
                    'conditions' => $rule->conditions,
                    'actions' => $actions,
                ];

                return $actions;
            }
        }

        return null;
    }

    private function applyWeatherPricing(ServiceType $serviceType): ?array
    {
        $weather = $this->getCurrentWeather();
        if (! $weather) {
            return null;
        }

        $surgeMultiplier = 1.0;

        // Snow/ice conditions
        if ($weather['precipitation'] > 0 && $weather['temperature'] < 2) {
            $surgeMultiplier *= 1.3;
            $this->calculationSteps[] = [
                'step' => 'Weather Pricing',
                'condition' => 'Snow/Ice',
                'surge_multiplier' => 1.3,
            ];
        }

        // Heavy rain
        if ($weather['precipitation'] > 5) {
            $surgeMultiplier *= 1.2;
            $this->calculationSteps[] = [
                'step' => 'Weather Pricing',
                'condition' => 'Heavy Rain',
                'surge_multiplier' => 1.2,
            ];
        }

        // Strong wind
        if ($weather['wind_speed'] > 15) {
            $surgeMultiplier *= 1.1;
            $this->calculationSteps[] = [
                'step' => 'Weather Pricing',
                'condition' => 'Strong Wind',
                'surge_multiplier' => 1.1,
            ];
        }

        if ($surgeMultiplier > 1.0) {
            $this->appliedRules[] = [
                'type' => 'weather_pricing',
                'weather' => $weather,
                'surge_multiplier' => $surgeMultiplier,
            ];

            return ['surge_multiplier' => $surgeMultiplier];
        }

        return null;
    }

    private function applyTimeBasedPricing(ServiceType $serviceType): ?array
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;

        $surgeMultiplier = 1.0;

        // Night time (22:00 - 06:00)
        if ($hour >= 22 || $hour <= 6) {
            $surgeMultiplier *= 1.4;
            $this->calculationSteps[] = [
                'step' => 'Time-based Pricing',
                'condition' => 'Night Time',
                'surge_multiplier' => 1.4,
            ];
        }

        // Peak hours (17:00 - 20:00)
        elseif ($hour >= 17 && $hour <= 20) {
            $surgeMultiplier *= 1.2;
            $this->calculationSteps[] = [
                'step' => 'Time-based Pricing',
                'condition' => 'Peak Hours',
                'surge_multiplier' => 1.2,
            ];
        }

        // Weekend pricing
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            $surgeMultiplier *= 1.1;
            $this->calculationSteps[] = [
                'step' => 'Time-based Pricing',
                'condition' => 'Weekend',
                'surge_multiplier' => 1.1,
            ];
        }

        if ($surgeMultiplier > 1.0) {
            $this->appliedRules[] = [
                'type' => 'time_based_pricing',
                'hour' => $hour,
                'day_of_week' => $dayOfWeek,
                'surge_multiplier' => $surgeMultiplier,
            ];

            return ['surge_multiplier' => $surgeMultiplier];
        }

        return null;
    }

    private function applySlotOverloadPricing(ServiceType $serviceType): ?array
    {
        $scheduledAt = $this->contextData['scheduled_at'] ?? now();
        $slot = $this->getSlotForTime($scheduledAt);

        if (! $slot) {
            return null;
        }

        $overloadPercentage = $slot->getOverbookingPercentage();
        $surgeMultiplier = 1.0;

        if ($overloadPercentage > 0.8) {
            $surgeMultiplier = 1.5;
        } elseif ($overloadPercentage > 0.6) {
            $surgeMultiplier = 1.3;
        } elseif ($overloadPercentage > 0.4) {
            $surgeMultiplier = 1.1;
        }

        if ($surgeMultiplier > 1.0) {
            $this->calculationSteps[] = [
                'step' => 'Slot Overload Pricing',
                'overload_percentage' => $overloadPercentage,
                'surge_multiplier' => $surgeMultiplier,
            ];

            $this->appliedRules[] = [
                'type' => 'slot_overload_pricing',
                'slot_id' => $slot->id,
                'overload_percentage' => $overloadPercentage,
                'surge_multiplier' => $surgeMultiplier,
            ];

            return ['surge_multiplier' => $surgeMultiplier];
        }

        return null;
    }

    private function evaluateRuleConditions(PricingContextRule $rule): bool
    {
        $conditions = $rule->conditions;

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition(array $condition): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! $field || ! $operator) {
            return false;
        }

        $contextValue = $this->getContextValue($field);

        return match ($operator) {
            'eq' => $contextValue == $value,
            'ne' => $contextValue != $value,
            'gt' => $contextValue > $value,
            'gte' => $contextValue >= $value,
            'lt' => $contextValue < $value,
            'lte' => $contextValue <= $value,
            'in' => in_array($contextValue, $value),
            'not_in' => ! in_array($contextValue, $value),
            'contains' => str_contains($contextValue, $value),
            'regex' => preg_match($value, $contextValue),
            default => false
        };
    }

    private function getContextValue(string $field)
    {
        return match ($field) {
            'latitude' => $this->contextData['latitude'] ?? null,
            'longitude' => $this->contextData['longitude'] ?? null,
            'scheduled_at' => $this->contextData['scheduled_at'] ?? null,
            'hour' => now()->hour,
            'day_of_week' => now()->dayOfWeek,
            'weather_precipitation' => $this->getCurrentWeather()['precipitation'] ?? 0,
            'weather_temperature' => $this->getCurrentWeather()['temperature'] ?? 20,
            'weather_wind_speed' => $this->getCurrentWeather()['wind_speed'] ?? 0,
            'slot_overload' => $this->getSlotOverloadPercentage(),
            default => null
        };
    }

    private function executeRuleActions(PricingContextRule $rule): array
    {
        $actions = $rule->actions;
        $result = [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? null;
            $value = $action['value'] ?? null;

            switch ($type) {
                case 'surge_multiplier':
                    $result['surge_multiplier'] = $value;
                    break;
                case 'discount_amount':
                    $result['discount_amount'] = $value;
                    break;
                case 'discount_percentage':
                    $result['discount_amount'] = ($this->contextData['base_price'] ?? 0) * ($value / 100);
                    break;
            }
        }

        return $result;
    }

    private function getCurrentWeather(): ?array
    {
        return Cache::remember('current_weather', 300, function () {
            $weather = WeatherData::latest()->first();

            return $weather ? $weather->toArray() : null;
        });
    }

    private function getSlotForTime($scheduledAt)
    {
        // Implementation to get slot for scheduled time
        return null; // Placeholder
    }

    private function getSlotOverloadPercentage(): float
    {
        $slot = $this->getSlotForTime($this->contextData['scheduled_at'] ?? now());

        return $slot ? $slot->getOverbookingPercentage() : 0;
    }

    private function calculateEstimatedDelivery(ServiceType $serviceType, array $context): ?string
    {
        $baseDuration = $serviceType->estimated_duration ?? 60; // minutes

        // Adjust for weather
        $weather = $this->getCurrentWeather();
        if ($weather) {
            if ($weather['precipitation'] > 0) {
                $baseDuration *= 1.2;
            }
            if ($weather['wind_speed'] > 15) {
                $baseDuration *= 1.1;
            }
        }

        // Adjust for time of day
        $hour = now()->hour;
        if ($hour >= 17 && $hour <= 20) {
            $baseDuration *= 1.3; // Peak hours
        }

        return now()->addMinutes($baseDuration)->toISOString();
    }

    private function logCalculation(ServiceType $serviceType, array $result): void
    {
        PricingCalculationLog::create([
            'service_type_id' => $serviceType->id,
            'input_context' => $this->contextData,
            'applied_rules' => $this->appliedRules,
            'calculation_steps' => $this->calculationSteps,
            'base_price' => $result['base_price'],
            'final_price' => $result['total_price'],
            'surge_multiplier' => $result['surge_multiplier'],
            'discount_amount' => $result['discount_amount'],
        ]);
    }

    private function isExperimentApplicable(AbExperiment $experiment, ServiceType $serviceType): bool
    {
        $params = $experiment->params ?? [];

        // Check if experiment applies to this service type
        if (isset($params['service_types']) && ! in_array($serviceType->id, $params['service_types'])) {
            return false;
        }

        // Check other applicability conditions
        return true;
    }

    private function getExperimentVariant(AbExperiment $experiment): ?array
    {
        $userId = auth()->id();
        $partnerId = $this->getPartnerId();

        // Check existing assignment
        $assignment = AbAssignment::where('experiment_id', $experiment->id)
            ->where(function ($query) use ($userId, $partnerId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                }
                if ($partnerId) {
                    $query->orWhere('partner_id', $partnerId);
                }
            })
            ->first();

        if ($assignment) {
            return $this->getVariantConfig($experiment, $assignment->variant);
        }

        // Create new assignment
        $variant = $this->assignVariant($experiment);
        if ($variant) {
            AbAssignment::create([
                'experiment_id' => $experiment->id,
                'user_id' => $userId,
                'partner_id' => $partnerId,
                'variant' => $variant,
                'assigned_at' => now(),
            ]);

            return $this->getVariantConfig($experiment, $variant);
        }

        return null;
    }

    private function assignVariant(AbExperiment $experiment): ?string
    {
        $variants = $experiment->variants ?? [];
        $trafficAllocation = $experiment->traffic_allocation ?? [];

        if (empty($variants) || empty($trafficAllocation)) {
            return null;
        }

        $random = mt_rand(1, 100);
        $cumulative = 0;

        foreach ($trafficAllocation as $variant => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        return array_key_first($variants);
    }

    private function getVariantConfig(AbExperiment $experiment, string $variant): ?array
    {
        $variants = $experiment->variants ?? [];

        return $variants[$variant] ?? null;
    }

    private function getPartnerId(): ?string
    {
        // Get partner ID from context or token
        return $this->contextData['partner_id'] ?? null;
    }
}

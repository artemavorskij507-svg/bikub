<?php

namespace App\Services\Orders;

use InvalidArgumentException;

class OrderScenarioRegistry
{
    public function all(): array
    {
        return config('order_scenarios.scenarios', []);
    }

    public function enabled(?string $categorySlug = null): array
    {
        return array_values(array_filter($this->all(), function (array $scenario) use ($categorySlug): bool {
            if (($scenario['enabled'] ?? false) !== true) {
                return false;
            }

            if ($categorySlug === null) {
                return true;
            }

            return ($scenario['category_slug'] ?? null) === $categorySlug;
        }));
    }

    public function get(string $code): array
    {
        $scenario = $this->all()[$code] ?? null;

        if (! is_array($scenario)) {
            throw new InvalidArgumentException("Unknown order scenario [{$code}].");
        }

        return ['code' => $code] + $scenario;
    }

    public function getEnabled(string $code): array
    {
        $scenario = $this->get($code);

        if (($scenario['enabled'] ?? false) !== true) {
            throw new InvalidArgumentException("Order scenario [{$code}] is disabled.");
        }

        return $scenario;
    }

    public function exists(string $code): bool
    {
        return isset($this->all()[$code]);
    }

    public function forCategory(string $slug): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $scenario): bool => ($scenario['category_slug'] ?? null) === $slug
        ));
    }

    public function forDomain(string $domain): array
    {
        return array_filter(
            $this->all(),
            static fn (array $scenario): bool => ($scenario['service_domain'] ?? null) === $domain
        );
    }

    public function publicMetadata(array $scenario): array
    {
        return [
            'key' => $scenario['key'] ?? $scenario['code'] ?? null,
            'title' => $scenario['title'] ?? null,
            'public_title' => $scenario['public_title'] ?? null,
            'short_description' => $scenario['short_description'] ?? null,
            'category_slug' => $scenario['category_slug'] ?? null,
            'service_type' => $scenario['service_type'] ?? null,
            'flow_type' => $scenario['flow_type'] ?? null,
            'pricing_model' => $scenario['pricing_model'] ?? null,
            'base_price' => $scenario['base_price'] ?? null,
            'currency' => $scenario['currency'] ?? config('order_scenarios.default_currency', 'NOK'),
            'sla_minutes' => $scenario['sla_minutes'] ?? null,
            'required_fields' => $scenario['required_fields'] ?? [],
            'optional_fields' => $scenario['optional_fields'] ?? [],
            'address_fields' => $scenario['address_fields'] ?? [],
            'checkout_steps' => $scenario['checkout_steps'] ?? [],
            'public_cta' => $scenario['public_cta'] ?? null,
            'tracker_template' => $scenario['tracker_template'] ?? 'default',
            'status_flow' => $scenario['status_flow'] ?? [],
        ];
    }

    public function adminMetadata(array $scenario): array
    {
        return $this->publicMetadata($scenario) + [
            'assignment_mode' => $scenario['assignment_mode'] ?? null,
            'allowed_worker_roles' => $scenario['allowed_worker_roles'] ?? [],
            'partner_required' => (bool) ($scenario['partner_required'] ?? false),
            'payment_required' => (bool) ($scenario['payment_required'] ?? false),
            'reservation_required' => (bool) ($scenario['reservation_required'] ?? false),
            'admin_group' => $scenario['admin_group'] ?? 'Operations',
            'enabled' => (bool) ($scenario['enabled'] ?? false),
        ];
    }

    public function validateRequiredFields(array $scenario, array $payload): array
    {
        $missing = [];
        foreach ($scenario['required_fields'] ?? [] as $field) {
            $value = data_get($payload, $field);
            if ($value === null || $value === '' || $value === []) {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}

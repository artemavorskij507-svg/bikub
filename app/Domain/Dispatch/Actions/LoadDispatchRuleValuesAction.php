<?php

namespace App\Domain\Dispatch\Actions;

use App\Domain\Dispatch\Models\DispatchRuleSet;

class LoadDispatchRuleValuesAction
{
    public function execute(string|int|null $organizationId, string|int|null $tenantId, string $serviceDomain, ?string $jobKind = null): array
    {
        $rows = DispatchRuleSet::query()
            ->where('is_active', true)
            ->where('service_domain', $serviceDomain)
            ->where(function ($q) use ($jobKind): void {
                if ($jobKind) {
                    $q->where('job_kind', $jobKind)->orWhereNull('job_kind');
                } else {
                    $q->whereNull('job_kind');
                }
            })
            ->where(function ($q) use ($organizationId): void {
                if ($organizationId !== null && $organizationId !== '') {
                    $q->where('organization_id', (string) $organizationId)->orWhereNull('organization_id');
                } else {
                    $q->whereNull('organization_id');
                }
            })
            ->where(function ($q) use ($tenantId): void {
                if ($tenantId !== null && $tenantId !== '') {
                    $q->where('tenant_id', (string) $tenantId)->orWhereNull('tenant_id');
                } else {
                    $q->whereNull('tenant_id');
                }
            })
            ->orderByRaw('CASE WHEN job_kind IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN organization_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get(['rule_key', 'rule_value_json']);

        $overrides = [];
        foreach ($rows as $row) {
            $value = $row->rule_value_json;
            if (is_array($value) && array_key_exists('value', $value) && count($value) === 1) {
                $value = $value['value'];
            }
            $this->setByPath($overrides, (string) $row->rule_key, $value);
        }

        return $overrides;
    }

    private function setByPath(array &$target, string $path, mixed $value): void
    {
        $segments = array_filter(explode('.', trim($path)), fn ($segment) => $segment !== '');
        if ($segments === []) {
            return;
        }

        $cursor = &$target;
        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $cursor[$segment] = $value;
                return;
            }
            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
    }
}


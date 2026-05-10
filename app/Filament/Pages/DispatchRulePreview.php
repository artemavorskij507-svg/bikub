<?php

namespace App\Filament\Pages;

use App\Domain\Dispatch\Actions\NormalizeDispatchRuleKeyAction;
use App\Domain\Dispatch\Actions\PreviewDispatchCandidateScoringAction;
use App\Domain\Dispatch\Actions\ValidateDispatchRuleValueAction;
use App\Models\Operations\ServiceJob;
use App\Support\Dispatch\DispatchRuleCatalog;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class DispatchRulePreview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Dispatch Rule Preview';
    protected static ?int $navigationSort = 8;
    protected static ?string $slug = 'dispatch-rule-preview';
    protected static string $view = 'filament.pages.dispatch-rule-preview';

    public ?int $jobId = null;
    public ?string $serviceDomain = null;
    public ?string $ruleKey = null;
    public ?string $ruleValue = null;
    public array $previewRows = [];
    public array $errors = [];
    public array $ruleInsight = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->hasRole('admin') || $user?->hasPermission('ops.rules.preview');
    }

    public function getTitle(): string
    {
        return 'Dispatch Rule Preview';
    }

    public function getJobsProperty()
    {
        $q = ServiceJob::query()->latest('id')->limit(50);
        if ($this->serviceDomain) {
            $q->where('service_domain', $this->serviceDomain);
        }

        return $q->get(['id', 'service_domain', 'job_kind', 'status']);
    }

    public function getRuleOptionsProperty(): array
    {
        return DispatchRuleCatalog::options();
    }

    public function runPreview(PreviewDispatchCandidateScoringAction $previewAction, NormalizeDispatchRuleKeyAction $normalizeKey, ValidateDispatchRuleValueAction $validateValue): void
    {
        $this->errors = [];
        $this->ruleInsight = [];
        $job = $this->jobId ? ServiceJob::query()->find($this->jobId) : null;
        if (! $job) {
            $this->errors[] = 'Select a valid job first.';
            return;
        }

        try {
            $key = $normalizeKey->execute((string) $this->ruleKey);
            $value = $validateValue->execute($key, $this->ruleValue);
            $result = $previewAction->execute($job, ['rule_key' => $key, 'value' => $value]);
            $this->previewRows = $result['rows'] ?? [];
            $defaultValue = DispatchRuleCatalog::defaultValueFor((string) $job->service_domain, $key);
            $effectiveRuntimeValue = data_get($result['new_rules'] ?? [], $key);
            $deltaPercent = DispatchRuleCatalog::deltaPercent($defaultValue, $value);
            $impact = DispatchRuleCatalog::impactLevel((string) $job->service_domain, $key, $value);
            $this->ruleInsight = [
                'rule_key' => $key,
                'default_value' => $this->formatValue($defaultValue),
                'override_value' => $this->formatValue($value),
                'effective_runtime_value' => $this->formatValue($effectiveRuntimeValue),
                'delta_percent' => $deltaPercent,
                'impact' => $impact,
                'impact_label' => $this->impactLabel($impact),
            ];
        } catch (\Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function impactLabel(string $impact): string
    {
        return match ($impact) {
            'high_impact' => 'High impact',
            'aggressive_override' => 'Aggressive override',
            default => 'Normal',
        };
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}

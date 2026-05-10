<?php

namespace App\Domain\Dispatch\Actions;

class ComputeLoadModifierAction
{
    public function execute(int $activeJobsCount, array $runtimeRules): array
    {
        $modifiers = (array) data_get($runtimeRules, 'modifiers', []);
        $scale = (float) ($modifiers['load_penalty_scale'] ?? 1.0);
        $idleBoost = (float) ($modifiers['idle_executor_boost'] ?? 4);
        $load1 = (float) ($modifiers['load_1_penalty'] ?? 0);
        $load2 = (float) ($modifiers['load_2_penalty'] ?? -6);
        $load3Plus = (float) ($modifiers['load_3_plus_penalty'] ?? -12);

        $base = match (true) {
            $activeJobsCount <= 0 => $idleBoost,
            $activeJobsCount === 1 => $load1,
            $activeJobsCount === 2 => $load2,
            default => $load3Plus,
        };

        return [
            'modifier' => round($base * $scale, 2),
            'reason' => 'executor_current_load',
            'active_jobs_count' => $activeJobsCount,
        ];
    }
}


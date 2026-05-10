<?php

namespace App\Domain\Ops\Queries;

class CandidateCompareQuery
{
    public function __construct(
        private readonly LiveJobDrawerQuery $liveJobDrawerQuery,
    ) {}

    public function execute(string $organizationId, int $jobId, ?int $leftExecutorId = null, ?int $rightExecutorId = null): array
    {
        $drawer = $this->liveJobDrawerQuery->execute($organizationId, $jobId);
        $candidates = collect((array) data_get($drawer, 'dispatch_candidates', []))
            ->values();

        $left = $leftExecutorId
            ? $candidates->first(fn (array $candidate): bool => (int) data_get($candidate, 'executor_id', 0) === $leftExecutorId)
            : $candidates->get(0);
        $right = $rightExecutorId
            ? $candidates->first(fn (array $candidate): bool => (int) data_get($candidate, 'executor_id', 0) === $rightExecutorId)
            : $candidates->get(1);

        $recommended = $candidates
            ->sortByDesc(fn (array $candidate) => [
                (bool) data_get($candidate, 'is_eligible', false) ? 1 : 0,
                (float) data_get($candidate, 'score_total', data_get($candidate, 'score', 0)),
            ])
            ->first();

        return [
            'job_id' => $jobId,
            'left' => $left,
            'right' => $right,
            'recommended_executor_id' => (int) data_get($recommended, 'executor_id', 0) ?: null,
            'eta_strategy_diff' => [
                'left' => (array) data_get($left, 'routing', []),
                'right' => (array) data_get($right, 'routing', []),
            ],
            'all_candidates' => $candidates->map(fn (array $candidate): array => [
                'executor_id' => (int) data_get($candidate, 'executor_id', 0),
                'executor_name' => (string) data_get($candidate, 'executor_name', data_get($candidate, 'display_name', '-')),
                'eligible' => (bool) data_get($candidate, 'is_eligible', false),
                'score' => (float) data_get($candidate, 'score_total', data_get($candidate, 'score', 0)),
            ])->values()->all(),
            'runtime' => (array) data_get($drawer, 'runtime', []),
        ];
    }
}

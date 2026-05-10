<?php

namespace App\Domain\Moving\Actions;

use App\Domain\Dispatch\Actions\CheckCapacityFitAction;
use App\Domain\Dispatch\Actions\CheckExecutorShiftEligibilityAction;
use App\Domain\Moving\Actions\ComputeMovingTeamEtaAction;
use App\Models\Operations\Executor;
use App\Models\Operations\ServiceJob;

class BuildMovingTeamCandidatesAction
{
    public function __construct(
        private readonly CheckExecutorShiftEligibilityAction $checkExecutorShiftEligibilityAction,
        private readonly CheckCapacityFitAction $checkCapacityFitAction,
        private readonly ComputeMovingTeamEtaAction $computeMovingTeamEtaAction,
    ) {}

    public function execute(ServiceJob $job, int $teamSize = 2, ?callable $etaResolver = null): array
    {
        $requiredTeamSize = max(1, (int) ($job->required_team_size ?: $teamSize));
        $pool = Executor::query()
            ->where('organization_id', $job->organization_id)
            ->where('is_dispatchable', true)
            ->whereIn('status', ['available', 'busy'])
            ->limit(50)
            ->get();

        $eligible = [];
        foreach ($pool as $executor) {
            $shift = $this->checkExecutorShiftEligibilityAction->execute($job, $executor);
            $capacity = $this->checkCapacityFitAction->execute($job, $executor);
            if ($shift['eligible'] && $capacity['fits']) {
                $eligible[] = $executor;
            }
        }

        $members = collect($eligible)->take($requiredTeamSize)->values();
        $memberEtas = $members
            ->map(fn (Executor $executor) => [
                'executor_id' => $executor->id,
                'eta_seconds' => $etaResolver ? max(0, (int) $etaResolver($executor)) : 1800,
            ])
            ->values()
            ->all();
        $eta = $this->computeMovingTeamEtaAction->execute($memberEtas);

        return [
            'fits' => $members->count() >= $requiredTeamSize,
            'team_size_requested' => $requiredTeamSize,
            'team_size_found' => $members->count(),
            'member_executor_ids' => $members->pluck('id')->all(),
            'member_etas' => $eta['member_etas'],
            'team_eta_seconds' => $eta['team_eta_seconds'],
            'required_team_size' => $requiredTeamSize,
            'selected_team_lead_executor_id' => $members->first()?->id,
        ];
    }
}

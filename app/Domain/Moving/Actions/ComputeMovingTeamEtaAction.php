<?php

namespace App\Domain\Moving\Actions;

class ComputeMovingTeamEtaAction
{
    /**
     * @param  array<int,array{executor_id:int,eta_seconds:int}>  $memberEtas
     * @return array{team_eta_seconds:int,member_etas:array<int,array{executor_id:int,eta_seconds:int}>}
     */
    public function execute(array $memberEtas): array
    {
        $normalized = array_values(array_map(function (array $item): array {
            return [
                'executor_id' => (int) ($item['executor_id'] ?? 0),
                'eta_seconds' => max(0, (int) ($item['eta_seconds'] ?? 0)),
            ];
        }, $memberEtas));

        $teamEta = 0;
        foreach ($normalized as $entry) {
            $teamEta = max($teamEta, $entry['eta_seconds']);
        }

        return [
            'team_eta_seconds' => $teamEta,
            'member_etas' => $normalized,
        ];
    }
}


<?php

namespace App\Domain\AgentOS\Policies;

use App\Domain\AgentOS\Enums\AgentStepStatus;

class StepTransitionPolicy
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function matrix(): array
    {
        return [
            AgentStepStatus::QUEUED->value => [
                AgentStepStatus::WAITING_DEPENDENCIES->value,
                AgentStepStatus::EXECUTING->value,
                AgentStepStatus::BLOCKED->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::WAITING_DEPENDENCIES->value => [
                AgentStepStatus::QUEUED->value,
                AgentStepStatus::EXECUTING->value,
                AgentStepStatus::BLOCKED->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::EXECUTING->value => [
                AgentStepStatus::ARTIFACT_GENERATED->value,
                AgentStepStatus::BLOCKED->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::ARTIFACT_GENERATED->value => [
                AgentStepStatus::READY_FOR_REVIEW->value,
                AgentStepStatus::VALIDATION_FAILED->value,
                AgentStepStatus::BLOCKED->value,
            ],
            AgentStepStatus::VALIDATION_FAILED->value => [
                AgentStepStatus::NEEDS_REVISION->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::NEEDS_REVISION->value => [
                AgentStepStatus::QUEUED->value,
                AgentStepStatus::BLOCKED->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::READY_FOR_REVIEW->value => [
                AgentStepStatus::APPROVED->value,
                AgentStepStatus::NEEDS_REVISION->value,
                AgentStepStatus::BLOCKED->value,
            ],
            AgentStepStatus::APPROVED->value => [
                AgentStepStatus::COMPLETED->value,
                AgentStepStatus::BLOCKED->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::BLOCKED->value => [
                AgentStepStatus::QUEUED->value,
                AgentStepStatus::FAILED->value,
            ],
            AgentStepStatus::COMPLETED->value => [],
            AgentStepStatus::FAILED->value => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        $allowed = self::matrix()[$from] ?? [];

        return in_array($to, $allowed, true);
    }
}

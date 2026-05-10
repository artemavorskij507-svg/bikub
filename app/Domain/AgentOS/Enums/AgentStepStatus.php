<?php

namespace App\Domain\AgentOS\Enums;

enum AgentStepStatus: string
{
    case QUEUED = 'queued';
    case WAITING_DEPENDENCIES = 'waiting_dependencies';
    case EXECUTING = 'executing';
    case ARTIFACT_GENERATED = 'artifact_generated';
    case VALIDATION_FAILED = 'validation_failed';
    case NEEDS_REVISION = 'needs_revision';
    case READY_FOR_REVIEW = 'ready_for_review';
    case APPROVED = 'approved';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    case FAILED = 'failed';
}

<?php

namespace App\Domain\AgentOS\Enums;

enum AgentRunStatus: string
{
    case QUEUED = 'queued';
    case PLANNING = 'planning';
    case EXECUTING = 'executing';
    case WAITING_DEPENDENCIES = 'waiting_dependencies';
    case VALIDATION_FAILED = 'validation_failed';
    case NEEDS_REVISION = 'needs_revision';
    case READY_FOR_REVIEW = 'ready_for_review';
    case APPROVED = 'approved';
    case AUDIT_COMPLETED = 'audit_completed';
    case FOLLOWUP_REQUIRED = 'followup_required';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    case FAILED = 'failed';
    case DEPLOY_READY = 'deploy_ready';
    case DEPLOYING = 'deploying';
    case DEPLOYED = 'deployed';
    case ROLLBACK_REQUIRED = 'rollback_required';
}

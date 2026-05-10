# Agent OS + Team Chat Production Runbook

## Scope
- `POST /api/agency-agents/runs` creates an Agent OS run and enqueues `ProcessAgentRunJob`.
- Team Chat (`/admin/a-i-agent-team-chat`) uses async acknowledgment + background execution.
- Source of truth: `agent_runs`, `agent_steps`, `agent_artifacts`, `agent_validations`, `agent_memories`.

## Feature Flags
Set in `.env`:

```env
AGENT_OS_ENABLED=true
AGENT_OS_EXECUTION_MODE=sync
AGENT_OS_API_ASYNC_ENABLED=true
AGENT_OS_CHAT_ASYNC_ENABLED=true
AGENT_OS_CHAT_QUEUE=default
AGENT_OS_CHAT_SYNC_FALLBACK_ON_DISPATCH_FAIL=false
AGENT_OS_AUTO_FOLLOWUP_ON_FINDINGS=true

AGENT_OS_TOOL_BROWSER=false
AGENT_OS_TOOL_RESEARCH=false
AGENT_OS_TOOL_CODE=false
AGENT_OS_TOOL_FALLBACK_ENABLED=true

AGENT_OS_DEPLOY_STAGING=false
AGENT_OS_DEPLOY_PRODUCTION=false
```

## Security Controls
- API routes are protected by:
  - `auth:sanctum`
  - `throttle:agency-agents`
  - `AgentRunPolicy`, `AgentStepPolicy` (strict org/tenant ownership)
- User input in Team Chat is sanitized before persistence/execution.
- Critical status transitions are written to application logs.

## Queue/Scheduler Requirements
Run workers:

```bash
php artisan horizon:terminate
php artisan queue:restart
```

Scheduler:
- `DetectStaleAgentStepsJob` every minute
- `CompactAgentMemoriesJob` daily

Ensure cron is configured:

```bash
* * * * * cd /var/www/bikube && php artisan schedule:run >> /dev/null 2>&1
```

## Deployment Checklist
1. Pull latest code from git artifact.
2. `php artisan optimize:clear`
3. `php artisan migrate --force`
4. `php artisan config:cache`
5. Restart queue workers (`horizon:terminate`, `queue:restart`).
6. Verify:
   - Team Chat opens without 500.
   - `POST /api/agency-agents/runs` returns `202 Accepted`.
   - Run progresses to terminal status in background.

## Rollback
Disable async without redeploy:

```env
AGENT_OS_API_ASYNC_ENABLED=false
AGENT_OS_CHAT_ASYNC_ENABLED=false
AGENT_OS_CHAT_SYNC_FALLBACK_ON_DISPATCH_FAIL=true
```

Then:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan queue:restart
```

## Observability
Track:
- ACK latency (p95) for Team Chat submit
- queue lag (`horizon:status`)
- terminal status distribution (`completed/blocked/failed/ready_for_review/followup_required`)
- stale steps count (`/api/agency-agents/steps?stale=true`)
- auth denials in logs for `/api/agency-agents/*`


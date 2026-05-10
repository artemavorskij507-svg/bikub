<?php

namespace Tests\Feature\Ops\Support;

use App\Domain\Dispatch\Actions\ApplyDispatchRuleOverridesAction;
use App\Domain\Dispatch\Actions\BuildDomainAwareDispatchScoreAction;
use App\Domain\Dispatch\Actions\CheckCapacityFitAction;
use App\Domain\Dispatch\Actions\CheckExecutorShiftEligibilityAction;
use App\Domain\Dispatch\Actions\CheckTimeWindowFitAction;
use App\Domain\Dispatch\Actions\ComputeDomainPriorityModifierAction;
use App\Domain\Dispatch\Actions\ComputeLoadModifierAction;
use App\Domain\Dispatch\Actions\LoadDispatchRuleValuesAction;
use App\Domain\Dispatch\Actions\ResolveDispatchRuleSetAction;
use App\Domain\Dispatch\Actions\ResolveRuntimeDispatchRuleSetAction;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Dispatch\Models\DispatchRuleSet;
use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Domain\Dispatch\Models\ExecutorShift;
use App\Domain\Exceptions\Actions\OpenOperationExceptionAction;
use App\Domain\Moving\Actions\BuildMovingTeamCandidatesAction;
use App\Domain\Moving\Actions\ComputeMovingTeamEtaAction;
use App\Domain\Moving\Actions\CreateTeamAssignmentAction;
use App\Domain\Operations\Actions\UpdateServiceJobStatusAction;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Roadside\Actions\ApplyEmergencyAcceptanceTimeoutAction;
use App\Domain\Roadside\Actions\ApplyEmergencyPriorityOverrideAction;
use App\Domain\Roadside\Actions\FindNearestCapableEmergencyExecutorAction;
use App\Domain\Roadside\Actions\FindPreemptibleAssignmentsAction;
use App\Domain\Roadside\Actions\PreemptLowPriorityAssignmentAction;
use App\Jobs\CalculateDispatchCandidatesJob;
use App\Models\Operations\DispatchCandidate;
use App\Models\Operations\DispatchRun;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

trait OpsDispatchTestSupport
{
    protected function organizationId(): string
    {
        return '11111111-1111-4111-8111-111111111114';
    }

    protected function tenantId(): int
    {
        return 1;
    }

    protected function actingAsOpsAdmin(): User
    {
        Organization::query()->updateOrCreate(
            ['id' => $this->organizationId()],
            [
                'name' => 'Ops Test Org',
                'slug' => 'ops-test-org',
                'status' => 'active',
            ]
        );

        $user = User::factory()->create();
        $user->forceFill([
            'organization_id' => $this->organizationId(),
            'default_org_id' => $this->organizationId(),
        ])->save();

        $role = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            [
                'slug' => 'admin',
                'description' => 'Ops smoke admin',
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $role->id],
            ['assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        Sanctum::actingAs($user);

        return $user;
    }

    protected function createExecutor(array $overrides = []): Executor
    {
        return Executor::query()->create(array_merge([
            'organization_id' => $this->organizationId(),
            'tenant_id' => $this->tenantId(),
            'name' => 'Executor '.Str::random(6),
            'display_name' => 'Executor '.Str::random(4),
            'executor_type' => 'employee',
            'status' => 'available',
            'is_dispatchable' => true,
            'max_concurrent_jobs' => 10,
            'skills' => [],
            'capabilities' => [],
            'capacity' => [],
            'equipment' => [],
            'last_seen_at' => now(),
        ], $overrides));
    }

    protected function createShift(Executor $executor, array $overrides = []): ExecutorShift
    {
        return ExecutorShift::query()->create(array_merge([
            'organization_id' => $executor->organization_id,
            'tenant_id' => $executor->tenant_id,
            'executor_id' => $executor->id,
            'day_of_week' => (int) now()->dayOfWeekIso,
            'start_time' => now()->subHour()->format('H:i:s'),
            'end_time' => now()->addHours(4)->format('H:i:s'),
            'shift_date' => now()->toDateString(),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(4),
            'is_active' => true,
            'is_available' => true,
            'timezone' => 'UTC',
        ], $overrides));
    }

    protected function createBreak(Executor $executor, array $overrides = []): ExecutorBreak
    {
        return ExecutorBreak::query()->create(array_merge([
            'organization_id' => $executor->organization_id,
            'tenant_id' => $executor->tenant_id,
            'executor_id' => $executor->id,
            'shift_date' => now()->toDateString(),
            'break_start_at' => now()->subMinutes(10),
            'break_end_at' => now()->addMinutes(20),
            'type' => 'break',
            'is_paid' => false,
        ], $overrides));
    }

    protected function createServiceJob(array $overrides = []): ServiceJob
    {
        return ServiceJob::query()->create(array_merge([
            'organization_id' => $this->organizationId(),
            'tenant_id' => $this->tenantId(),
            'source_type' => 'smoke',
            'service_domain' => 'delivery',
            'job_kind' => 'smoke',
            'status' => 'pending_dispatch',
            'priority' => 'normal',
            'service_lat' => 50.4501,
            'service_lng' => 30.5234,
            'time_window_start' => now()->subMinute(),
            'time_window_end' => now()->addMinutes(60),
            'required_skills' => [],
            'required_capacity' => [],
            'required_equipment' => [],
            'metadata' => ['smoke' => true],
        ], $overrides));
    }

    protected function runDispatchForJob(ServiceJob $job): DispatchRun
    {
        app()->call([new CalculateDispatchCandidatesJob($job->id), 'handle']);

        return DispatchRun::query()->where('service_job_id', $job->id)->latest('id')->firstOrFail();
    }

    protected function latestCandidateForJob(ServiceJob $job): DispatchCandidate
    {
        return DispatchCandidate::query()
            ->where('service_job_id', $job->id)
            ->latest('id')
            ->firstOrFail();
    }

    protected function latestAssignmentForJob(ServiceJob $job): Assignment
    {
        return Assignment::query()
            ->where('service_job_id', $job->id)
            ->latest('id')
            ->firstOrFail();
    }

    protected function seedRuleOverride(ServiceJob $job, string $key, mixed $value): DispatchRuleSet
    {
        return DispatchRuleSet::query()->create([
            'organization_id' => (string) $job->organization_id,
            'tenant_id' => (string) ($job->tenant_id ?? $this->tenantId()),
            'service_domain' => (string) $job->service_domain,
            'job_kind' => $job->job_kind ?: $job->job_type,
            'rule_key' => $key,
            'rule_value_json' => is_array($value) ? $value : ['value' => $value],
            'is_active' => true,
        ]);
    }

    protected function mockRedis(array $values = []): void
    {
        Redis::shouldReceive('get')
            ->andReturnUsing(function (string $key) use ($values) {
                return $values[$key] ?? null;
            });

        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('eval')->andReturn(1);
    }

    protected function drawerVersion(ServiceJob $job): string
    {
        return optional($job->updated_at)->format('Y-m-d H:i:s.u')
            ?? now()->format('Y-m-d H:i:s.u');
    }
}

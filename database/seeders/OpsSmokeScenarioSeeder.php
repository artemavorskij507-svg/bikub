<?php

namespace Database\Seeders;

use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Dispatch\Models\DispatchRuleSet;
use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Domain\Dispatch\Models\ExecutorShift;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OpsSmokeScenarioSeeder extends Seeder
{
    private const ORG = '11111111-1111-4111-8111-111111111114';

    private const TENANT = 1;

    public function run(): void
    {
        $this->cleanup();

        $outOfShift = $this->makeExecutor('Smoke Delivery OutShift');
        $this->makeShift($outOfShift, now()->subHours(4), now()->subHours(2));

        $onBreak = $this->makeExecutor('Smoke Delivery OnBreak');
        $this->makeShift($onBreak, now()->subHours(1), now()->addHours(4));
        ExecutorBreak::query()->create([
            'organization_id' => self::ORG,
            'tenant_id' => (string) self::TENANT,
            'executor_id' => $onBreak->id,
            'shift_date' => now()->toDateString(),
            'break_start_at' => now()->subMinutes(15),
            'break_end_at' => now()->addMinutes(15),
            'type' => 'break',
            'is_paid' => false,
        ]);

        $handymanNoTool = $this->makeExecutor('Smoke Handyman NoTool', [
            'skills' => ['electricity'],
            'equipment' => ['drill'],
        ]);
        $this->makeShift($handymanNoTool, now()->subHour(), now()->addHours(5));

        $roadsideCapable = $this->makeExecutor('Smoke Roadside Capable', [
            'skills' => ['tow'],
            'capabilities' => ['tow', 'jump_start'],
        ]);
        $this->makeShift($roadsideCapable, now()->subHour(), now()->addHours(6));

        $roadsideIncapable = $this->makeExecutor('Smoke Roadside Incapable', [
            'skills' => ['diagnostics'],
            'capabilities' => ['diagnostics'],
        ]);
        $this->makeShift($roadsideIncapable, now()->subHour(), now()->addHours(6));

        $movingA = $this->makeExecutor('Smoke Moving A');
        $movingB = $this->makeExecutor('Smoke Moving B');
        $this->makeShift($movingA, now()->subHour(), now()->addHours(6));
        $this->makeShift($movingB, now()->subHour(), now()->addHours(6));

        $deliveryShiftJob = $this->makeJob('delivery', 'smoke_delivery_out_of_shift');
        $deliveryWindowJob = $this->makeJob('delivery', 'smoke_delivery_window_miss', [
            'time_window_end' => now()->addMinutes(5),
        ]);
        $this->makeJob('handyman', 'smoke_handyman_capacity_mismatch', [
            'required_equipment' => ['pipe_wrench'],
            'required_skills' => ['plumbing'],
        ]);

        $lowPriorityRoadside = $this->makeJob('roadside', 'smoke_roadside_low_priority', [
            'priority' => 'normal',
            'status' => 'assigned',
        ]);
        $lowPriorityAssignment = Assignment::query()->create([
            'organization_id' => self::ORG,
            'tenant_id' => self::TENANT,
            'service_job_id' => $lowPriorityRoadside->id,
            'executor_id' => $roadsideCapable->id,
            'status' => 'proposed',
            'assignment_mode' => 'auto_assign',
        ]);
        $lowPriorityRoadside->update([
            'executor_id' => $roadsideCapable->id,
            'assignment_id' => $lowPriorityAssignment->id,
        ]);

        $this->makeJob('roadside', 'smoke_roadside_emergency', [
            'priority' => 'emergency',
            'required_skills' => ['tow'],
        ]);
        $this->makeJob('roadside', 'smoke_roadside_no_capable', [
            'priority' => 'emergency',
            'required_equipment' => ['tow_truck'],
            'required_skills' => ['tow'],
        ]);

        $this->makeJob('moving', 'smoke_moving_team_eta', [
            'required_team_size' => 2,
        ]);

        DispatchRuleSet::query()->create([
            'organization_id' => self::ORG,
            'tenant_id' => (string) self::TENANT,
            'service_domain' => 'delivery',
            'job_kind' => $deliveryShiftJob->job_kind,
            'rule_key' => 'weights.eta',
            'rule_value_json' => ['value' => 0.55],
            'is_active' => true,
        ]);
        DispatchRuleSet::query()->create([
            'organization_id' => self::ORG,
            'tenant_id' => (string) self::TENANT,
            'service_domain' => 'delivery',
            'job_kind' => $deliveryWindowJob->job_kind,
            'rule_key' => 'modifiers.window_high_risk_penalty',
            'rule_value_json' => ['value' => -22],
            'is_active' => true,
        ]);
    }

    private function cleanup(): void
    {
        ServiceJob::query()
            ->where('job_kind', 'like', 'smoke_%')
            ->orWhere('source_type', 'smoke')
            ->delete();

        Executor::query()
            ->where('name', 'like', 'Smoke %')
            ->delete();

        DispatchRuleSet::query()
            ->where('job_kind', 'like', 'smoke_%')
            ->delete();
    }

    private function makeExecutor(string $name, array $overrides = []): Executor
    {
        return Executor::query()->create(array_merge([
            'organization_id' => self::ORG,
            'tenant_id' => self::TENANT,
            'name' => $name,
            'display_name' => $name,
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

    private function makeShift(Executor $executor, $startsAt, $endsAt): void
    {
        ExecutorShift::query()->create([
            'organization_id' => self::ORG,
            'tenant_id' => (string) self::TENANT,
            'executor_id' => $executor->id,
            'day_of_week' => (int) now()->dayOfWeekIso,
            'start_time' => $startsAt->format('H:i:s'),
            'end_time' => $endsAt->format('H:i:s'),
            'shift_date' => now()->toDateString(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
            'is_available' => true,
            'timezone' => 'UTC',
        ]);
    }

    private function makeJob(string $domain, string $kind, array $overrides = []): ServiceJob
    {
        return ServiceJob::query()->create(array_merge([
            'organization_id' => self::ORG,
            'tenant_id' => self::TENANT,
            'source_type' => 'smoke',
            'service_domain' => $domain,
            'job_kind' => $kind,
            'status' => 'pending_dispatch',
            'priority' => 'normal',
            'service_lat' => 50.4501,
            'service_lng' => 30.5234,
            'time_window_start' => now()->subMinute(),
            'time_window_end' => now()->addMinutes(60),
            'required_skills' => [],
            'required_capacity' => [],
            'required_equipment' => [],
            'metadata' => ['seed' => 'ops_smoke', 'token' => Str::random(6)],
        ], $overrides));
    }
}


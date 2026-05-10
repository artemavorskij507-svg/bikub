<?php

namespace Tests\Feature\Ops\Api;

use App\Domain\Operations\Models\ServiceJob;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\OpsControlPlanePermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Ops\Support\OpsDispatchTestSupport;
use Tests\TestCase;

class OpsWorkbenchRoleMatrixApiTest extends TestCase
{
    use OpsDispatchTestSupport;
    use RefreshDatabase;

    public function test_read_endpoints_follow_role_matrix(): void
    {
        $this->seed(OpsControlPlanePermissionsSeeder::class);
        $this->mockRedis();

        $job = $this->prepareJobForReadChecks();
        $cases = [
            ['role' => 'admin', 'allowed' => true],
            ['role' => 'ops_admin', 'allowed' => true],
            ['role' => 'ops_manager', 'allowed' => true],
            ['role' => 'ops_rules_admin', 'allowed' => true],
        ];

        foreach ($cases as $case) {
            $user = $this->createUserWithRole($case['role']);
            Sanctum::actingAs($user);

            $map = $this->getJson('/api/ops/map/live');
            $drawer = $this->getJson("/api/ops/jobs/{$job->id}/drawer");
            $compare = $this->getJson("/api/ops/jobs/{$job->id}/candidate-compare");
            $triage = $this->getJson('/api/ops/workbench/triage');
            $savedFilters = $this->getJson('/api/ops/workbench/saved-filters');

            if ($case['allowed']) {
                $map->assertOk();
                $drawer->assertOk();
                $compare->assertOk();
                $triage->assertOk();
                $savedFilters->assertOk();
            } else {
                $map->assertForbidden();
                $drawer->assertForbidden();
                $compare->assertForbidden();
                $triage->assertForbidden();
                $savedFilters->assertForbidden();
            }
        }
    }

    public function test_manual_dispatch_and_bulk_actions_follow_role_matrix(): void
    {
        $this->seed(OpsControlPlanePermissionsSeeder::class);
        $this->mockRedis();

        $cases = [
            ['role' => 'admin', 'dispatch' => 200, 'bulk_ack' => 200],
            ['role' => 'ops_admin', 'dispatch' => 200, 'bulk_ack' => 200],
            ['role' => 'ops_manager', 'dispatch' => 403, 'bulk_ack' => 200],
            ['role' => 'ops_rules_admin', 'dispatch' => 403, 'bulk_ack' => 403],
        ];

        foreach ($cases as $case) {
            $job = $this->prepareJobForDispatchChecks();
            $user = $this->createUserWithRole($case['role']);
            Sanctum::actingAs($user);

            $dispatch = $this->withHeader('X-Idempotency-Key', 'matrix-'.$case['role'].'-'.uniqid())
                ->postJson("/api/ops/jobs/{$job->id}/manual-dispatch", [
                    'executor_id' => $this->createExecutor()->id,
                    'expected_job_version' => $this->drawerVersion($job),
                    'notes' => 'matrix-check',
                ]);

            $dispatch->assertStatus($case['dispatch']);

            $bulk = $this->postJson('/api/ops/workbench/bulk-action', [
                'action' => 'exceptions_bulk_acknowledge',
                'ids' => [1],
            ]);

            $bulk->assertStatus($case['bulk_ack']);
        }
    }

    private function prepareJobForReadChecks(): ServiceJob
    {
        $executor = $this->createExecutor();
        $this->createShift($executor);
        $job = $this->createServiceJob([
            'service_domain' => 'delivery',
            'job_kind' => 'role-matrix-read',
        ]);
        $this->runDispatchForJob($job);

        return $job->fresh();
    }

    private function prepareJobForDispatchChecks(): ServiceJob
    {
        $executor = $this->createExecutor();
        $this->createShift($executor);

        return $this->createServiceJob([
            'service_domain' => 'delivery',
            'job_kind' => 'role-matrix-dispatch',
            'status' => 'pending_dispatch',
        ]);
    }

    private function createUserWithRole(string $roleName): User
    {
        Organization::query()->updateOrCreate(
            ['id' => $this->organizationId()],
            [
                'name' => 'Ops Matrix Org',
                'slug' => 'ops-matrix-org',
                'status' => 'active',
            ]
        );

        $user = User::factory()->create([
            'email' => $roleName.'-matrix-'.uniqid().'@example.test',
        ]);

        $user->forceFill([
            'organization_id' => $this->organizationId(),
            'default_org_id' => $this->organizationId(),
        ])->save();

        $role = Role::query()->where('name', $roleName)->first();
        if (! $role && $roleName === 'admin') {
            $role = Role::query()->firstOrCreate(
                ['name' => 'admin'],
                [
                    'slug' => 'admin',
                    'description' => 'Admin role for matrix test',
                    'permissions' => ['*'],
                    'is_active' => true,
                ]
            );
        }

        if (! $role) {
            $this->fail("Role {$roleName} not found. Ensure OpsControlPlanePermissionsSeeder is loaded.");
        }

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $role->id],
            ['assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        return $user->fresh();
    }
}


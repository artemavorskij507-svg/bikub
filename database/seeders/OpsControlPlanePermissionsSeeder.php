<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OpsControlPlanePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ops.shifts.viewAny',
            'ops.shifts.create',
            'ops.shifts.update',
            'ops.shifts.delete',
            'ops.breaks.viewAny',
            'ops.breaks.create',
            'ops.breaks.update',
            'ops.breaks.delete',
            'ops.rules.viewAny',
            'ops.rules.create',
            'ops.rules.update',
            'ops.rules.delete',
            'ops.rules.preview',
            'ops.service_jobs.viewAny',
            'ops.service_jobs.view',
            'ops.service_jobs.update',
            'ops.service_jobs.dispatch',
            'ops.exceptions.viewAny',
            'ops.exceptions.update',
            'ops.exceptions.resolve',
            'ops.executors.viewAny',
            'ops.executors.view',
            'ops.assignments.update',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ],
                []
            );
        }

        $this->upsertRole(
            name: 'ops_admin',
            slug: 'ops-admin',
            description: 'Operations admin for live workbench, shifts, and breaks.',
            permissions: [
                'ops.shifts.viewAny',
                'ops.shifts.create',
                'ops.shifts.update',
                'ops.shifts.delete',
                'ops.breaks.viewAny',
                'ops.breaks.create',
                'ops.breaks.update',
                'ops.breaks.delete',
                'ops.rules.viewAny',
                'ops.rules.preview',
                'ops.service_jobs.viewAny',
                'ops.service_jobs.view',
                'ops.service_jobs.update',
                'ops.service_jobs.dispatch',
                'ops.exceptions.viewAny',
                'ops.exceptions.update',
                'ops.exceptions.resolve',
                'ops.executors.viewAny',
                'ops.executors.view',
                'ops.assignments.update',
            ]
        );

        $this->upsertRole(
            name: 'ops_manager',
            slug: 'ops-manager',
            description: 'Operations manager with live workbench and exception handling access.',
            permissions: [
                'ops.shifts.viewAny',
                'ops.breaks.viewAny',
                'ops.rules.viewAny',
                'ops.rules.preview',
                'ops.service_jobs.viewAny',
                'ops.service_jobs.view',
                'ops.exceptions.viewAny',
                'ops.exceptions.update',
                'ops.exceptions.resolve',
                'ops.executors.viewAny',
                'ops.executors.view',
            ]
        );

        $this->upsertRole(
            name: 'ops_rules_admin',
            slug: 'ops-rules-admin',
            description: 'Rules admin with dispatch rules control and live diagnostics read access.',
            permissions: [
                'ops.rules.viewAny',
                'ops.rules.create',
                'ops.rules.update',
                'ops.rules.delete',
                'ops.rules.preview',
                'ops.shifts.viewAny',
                'ops.breaks.viewAny',
                'ops.service_jobs.viewAny',
                'ops.service_jobs.view',
                'ops.exceptions.viewAny',
                'ops.executors.viewAny',
                'ops.executors.view',
            ]
        );

        $this->command?->info('Ops control plane permissions seeded successfully.');
    }

    private function upsertRole(string $name, string $slug, string $description, array $permissions): void
    {
        $payload = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'permissions' => $permissions,
            'is_active' => true,
        ];

        if (Schema::hasColumn('roles', 'guard_name')) {
            $payload['guard_name'] = 'web';
        }

        $role = Role::query()->updateOrCreate(['name' => $name], $payload);

        try {
            if (method_exists($role, 'syncPermissions') && Schema::hasTable('role_has_permissions')) {
                $role->syncPermissions($permissions);
            }
        } catch (\Throwable $e) {
            // Fallback to JSON-based permissions only.
        }
    }
}

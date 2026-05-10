<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpsControlPlaneRoleAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $assignments = [
            'admin' => array_filter(array_map('trim', explode(',', (string) env('OPS_ADMIN_EMAILS', 'keks@glf.no')))),
            'ops_admin' => array_filter(array_map('trim', explode(',', (string) env('OPS_CP_OPS_ADMIN_EMAILS', 'oleksandr@glf.no')))),
            'ops_manager' => array_filter(array_map('trim', explode(',', (string) env('OPS_CP_OPS_MANAGER_EMAILS', 'maria@glf.no')))),
            'ops_rules_admin' => array_filter(array_map('trim', explode(',', (string) env('OPS_CP_OPS_RULES_ADMIN_EMAILS', 'eva.nystad@glf.no')))),
        ];

        foreach ($assignments as $roleName => $emails) {
            $role = Role::query()->where('name', $roleName)->first();
            if (! $role) {
                $this->command?->warn("Role {$roleName} not found.");
                continue;
            }

            foreach ($emails as $email) {
                $user = User::query()->where('email', $email)->first();
                if (! $user) {
                    $this->command?->warn("User {$email} not found.");
                    continue;
                }

                DB::table('user_roles')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                    ],
                    [
                        'assigned_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $this->command?->info("Assigned {$roleName} to {$email}");
            }
        }
    }
}


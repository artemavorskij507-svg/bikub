<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');

                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');

                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');

                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        $this->backfillPermissionsAndRoles();
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
    }

    private function backfillPermissionsAndRoles(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $roles = DB::table('roles')->select('id', 'guard_name', 'permissions')->get();

        foreach ($roles as $role) {
            $guardName = (string) ($role->guard_name ?: 'web');
            $permissionNames = $this->parsePermissionNames($role->permissions);

            foreach ($permissionNames as $name) {
                $name = trim((string) $name);
                if ($name === '' || $name === '*') {
                    continue;
                }

                DB::table('permissions')->updateOrInsert(
                    ['name' => $name, 'guard_name' => $guardName],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        foreach ($roles as $role) {
            $guardName = (string) ($role->guard_name ?: 'web');
            $permissionNames = $this->parsePermissionNames($role->permissions);
            $hasWildcard = in_array('*', $permissionNames, true);

            $permissionIds = collect();
            if ($hasWildcard) {
                $permissionIds = DB::table('permissions')
                    ->where('guard_name', $guardName)
                    ->pluck('id');
            } else {
                $permissionIds = DB::table('permissions')
                    ->where('guard_name', $guardName)
                    ->whereIn('name', array_values(array_filter($permissionNames, fn ($v) => $v !== '*')))
                    ->pluck('id');
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $role->id],
                    []
                );
            }
        }

        if (Schema::hasTable('user_roles')) {
            $userRoles = DB::table('user_roles')->select('user_id', 'role_id')->get();
            foreach ($userRoles as $row) {
                DB::table('model_has_roles')->updateOrInsert(
                    [
                        'role_id' => $row->role_id,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $row->user_id,
                    ],
                    []
                );
            }
        }
    }

    private function parsePermissionNames(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_map('strval', $decoded));
            }
        }

        return [];
    }
};


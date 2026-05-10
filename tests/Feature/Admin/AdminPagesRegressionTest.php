<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_entry_pages_do_not_return_500(): void
    {
        $admin = $this->get('/admin');
        $login = $this->get('/admin/login');
        $jobs = $this->get('/admin/service-jobs');
        $exceptions = $this->get('/admin/operation-exceptions');
        $map = $this->get('/admin/live-operations-map');

        $admin->assertStatus(302);
        $login->assertStatus(200);
        $jobs->assertStatus(302);
        $exceptions->assertStatus(302);
        $map->assertStatus(302);
    }

    public function test_authenticated_admin_pages_load_without_server_error(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-regression@example.test',
            'password' => bcrypt('secret'),
        ]);

        $role = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            [
                'slug' => 'admin',
                'description' => 'Admin for regression tests',
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $admin->id, 'role_id' => $role->id],
            ['assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        $this->actingAs($admin);

        $this->get('/admin')->assertStatus(200);
        $this->get('/admin/service-jobs')->assertStatus(200);
        $this->get('/admin/operation-exceptions')->assertStatus(200);
        $this->get('/admin/live-operations-map')->assertStatus(200);
        $this->get('/admin/dispatch-rule-sets')->assertStatus(200);
    }
}

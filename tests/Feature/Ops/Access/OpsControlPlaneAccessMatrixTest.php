<?php

namespace Tests\Feature\Ops\Access;

use App\Domain\Dispatch\Models\DispatchRuleSet;
use App\Domain\Dispatch\Models\ExecutorBreak;
use App\Domain\Dispatch\Models\ExecutorShift;
use App\Filament\Pages\DispatchRulePreview;
use App\Policies\DispatchRuleSetPolicy;
use App\Policies\ExecutorBreakPolicy;
use App\Policies\ExecutorShiftPolicy;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\OpsControlPlanePermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OpsControlPlaneAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_ops_control_plane_access_matrix(): void
    {
        $this->seed(OpsControlPlanePermissionsSeeder::class);

        $admin = $this->createUserWithRole('admin', 'admin@example.test');
        $opsAdmin = $this->createUserWithRole('ops_admin', 'ops-admin@example.test');
        $opsManager = $this->createUserWithRole('ops_manager', 'ops-manager@example.test');
        $opsRulesAdmin = $this->createUserWithRole('ops_rules_admin', 'ops-rules@example.test');
        $shiftPolicy = new ExecutorShiftPolicy();
        $breakPolicy = new ExecutorBreakPolicy();
        $rulesPolicy = new DispatchRuleSetPolicy();
        $shiftModel = new ExecutorShift();
        $breakModel = new ExecutorBreak();
        $rulesModel = new DispatchRuleSet();

        // admin: full access
        $this->assertTrue($admin->canAccessFilament());
        $this->assertTrue($shiftPolicy->viewAny($admin));
        $this->assertTrue($shiftPolicy->create($admin));
        $this->assertTrue($breakPolicy->viewAny($admin));
        $this->assertTrue($breakPolicy->create($admin));
        $this->assertTrue($rulesPolicy->viewAny($admin));
        $this->assertTrue($rulesPolicy->update($admin, $rulesModel));
        Auth::login($admin);
        $this->assertTrue(DispatchRulePreview::canAccess());
        Auth::logout();

        // ops_admin: shifts/breaks full, rules read-only + preview
        $this->assertTrue($opsAdmin->canAccessFilament());
        $this->assertTrue($shiftPolicy->viewAny($opsAdmin));
        $this->assertTrue($shiftPolicy->create($opsAdmin));
        $this->assertTrue($shiftPolicy->update($opsAdmin, $shiftModel));
        $this->assertTrue($shiftPolicy->delete($opsAdmin, $shiftModel));
        $this->assertTrue($breakPolicy->viewAny($opsAdmin));
        $this->assertTrue($breakPolicy->create($opsAdmin));
        $this->assertTrue($breakPolicy->update($opsAdmin, $breakModel));
        $this->assertTrue($breakPolicy->delete($opsAdmin, $breakModel));
        $this->assertTrue($rulesPolicy->viewAny($opsAdmin));
        $this->assertFalse($rulesPolicy->update($opsAdmin, $rulesModel));
        Auth::login($opsAdmin);
        $this->assertTrue(DispatchRulePreview::canAccess());
        Auth::logout();

        // ops_manager: view + preview, no rules edit/delete
        $this->assertTrue($opsManager->canAccessFilament());
        $this->assertTrue($shiftPolicy->viewAny($opsManager));
        $this->assertFalse($shiftPolicy->create($opsManager));
        $this->assertTrue($breakPolicy->viewAny($opsManager));
        $this->assertFalse($breakPolicy->create($opsManager));
        $this->assertTrue($rulesPolicy->viewAny($opsManager));
        $this->assertFalse($rulesPolicy->update($opsManager, $rulesModel));
        $this->assertFalse($rulesPolicy->delete($opsManager, $rulesModel));
        Auth::login($opsManager);
        $this->assertTrue(DispatchRulePreview::canAccess());
        Auth::logout();

        // ops_rules_admin: full rules + preview, shifts/breaks read-only
        $this->assertTrue($opsRulesAdmin->canAccessFilament());
        $this->assertTrue($shiftPolicy->viewAny($opsRulesAdmin));
        $this->assertFalse($shiftPolicy->create($opsRulesAdmin));
        $this->assertTrue($breakPolicy->viewAny($opsRulesAdmin));
        $this->assertFalse($breakPolicy->create($opsRulesAdmin));
        $this->assertTrue($rulesPolicy->viewAny($opsRulesAdmin));
        $this->assertTrue($rulesPolicy->create($opsRulesAdmin));
        $this->assertTrue($rulesPolicy->update($opsRulesAdmin, $rulesModel));
        $this->assertTrue($rulesPolicy->delete($opsRulesAdmin, $rulesModel));
        Auth::login($opsRulesAdmin);
        $this->assertTrue(DispatchRulePreview::canAccess());
        Auth::logout();
    }

    private function createUserWithRole(string $roleName, string $email): User
    {
        $user = User::query()->create([
            'name' => ucfirst(str_replace('_', ' ', $roleName)),
            'email' => $email,
            'password' => bcrypt('secret'),
        ]);

        $role = Role::query()->where('name', $roleName)->firstOrFail();

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }
}

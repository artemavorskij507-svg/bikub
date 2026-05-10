<?php

namespace Tests\Feature\Policies;

use App\Models\Delivery\DeliveryOrder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_delivery_orders(): void
    {
        $admin = User::factory()->create();
        $this->assignRole($admin, 'admin');

        $this->assertTrue($admin->can('viewAny', DeliveryOrder::class));
        $this->assertTrue($admin->can('update', DeliveryOrder::make()));
        $this->assertTrue($admin->can('create', DeliveryOrder::class));
    }

    public function test_dispatcher_can_manage_delivery_orders(): void
    {
        $dispatcher = User::factory()->create();
        $this->assignRole($dispatcher, 'dispatcher');

        $this->assertTrue($dispatcher->can('viewAny', DeliveryOrder::class));
        $this->assertTrue($dispatcher->can('update', DeliveryOrder::make()));
        $this->assertTrue($dispatcher->can('create', DeliveryOrder::class));
    }

    public function test_regular_user_cannot_access_delivery_orders(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', DeliveryOrder::class));
        $this->assertFalse($user->can('update', DeliveryOrder::make()));
        $this->assertFalse($user->can('create', DeliveryOrder::class));
    }

    protected function assignRole(User $user, string $roleName): void
    {
        $role = Role::firstWhere('name', $roleName);

        if (! $role) {
            $role = new Role([
                'name' => $roleName,
                'description' => ucfirst($roleName).' role',
                'is_active' => true,
            ]);
            $role->slug = $roleName;
            $role->save();
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}

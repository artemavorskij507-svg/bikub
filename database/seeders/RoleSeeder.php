<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'slug' => 'admin',
                'description' => 'Полный доступ к системе',
                'permissions' => ['*'],
                'is_active' => true,
            ],
            [
                'name' => 'operator',
                'slug' => 'operator',
                'description' => 'Управление заказами и планирование',
                'permissions' => [
                    'orders.view', 'orders.edit', 'orders.assign',
                    'users.view', 'users.edit',
                    'tasks.view', 'tasks.edit', 'tasks.assign',
                    'reports.view',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'courier',
                'slug' => 'courier',
                'description' => 'Выполнение заказов',
                'permissions' => [
                    'tasks.view', 'tasks.edit',
                    'orders.view',
                    'profile.edit',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'customer',
                'slug' => 'customer',
                'description' => 'Заказ услуг',
                'permissions' => [
                    'orders.view', 'orders.create',
                    'profile.edit',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('Roles seeded successfully!');
    }
}

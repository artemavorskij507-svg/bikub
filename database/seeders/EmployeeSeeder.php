<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $partner = Partner::where('slug', 'demo-logistics')->first() ?: Partner::first();

        if (! $partner) {
            $this->command?->warn('Партнер не знайдено. Спочатку запустіть PartnerSeeder!');

            return;
        }

        $staff = [
            ['name' => 'Олександр Іванов', 'email' => 'oleksandr@glf.no'],
            ['name' => 'Марія Петрова', 'email' => 'maria@glf.no'],
            ['name' => 'Ігор Сидоренко', 'email' => 'ihor@glf.no'],
        ];

        foreach ($staff as $person) {
            User::updateOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ]
            );
        }

        $users = User::whereIn('email', collect($staff)->pluck('email'))->get();

        foreach ($users as $index => $user) {
            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'partner_id' => $partner->id,
                    'employee_number' => 'EMP-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'first_name' => explode(' ', $user->name)[0] ?? 'Unknown',
                    'last_name' => explode(' ', $user->name)[1] ?? 'User',
                    'position' => $index === 0 ? 'Виконавець' : 'Асістент',
                    'status' => 'active',
                    'hire_date' => now()->subMonths(max(1, $index + 1)),
                    'skills' => ['Доставка', 'Складання меблів', 'Допомога'],
                    'background_check' => true,
                    'is_verified' => true,
                ]
            );
        }

        $this->command?->info('Співробітники успішно створені/оновлені!');
    }
}

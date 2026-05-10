<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\GeoZone;
use App\Models\User;
use Illuminate\Database\Seeder;

class NarvikWorkersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Narvik workers/executors...');

        $zones = GeoZone::whereIn('slug', ['narvik-sentrum', 'narvik-60km', 'ankenes', 'bjerkvik'])->get()->keyBy('slug');

        // ensure we have a user to attach employees to
        $defaultUser = User::where('email', 'keks@glf.no')->first() ?: User::first();
        if (! $defaultUser) {
            $defaultUser = User::create([
                'name' => 'narvik-worker-owner',
                'email' => 'narvik-worker-owner@example.test',
                'password' => bcrypt(str_random(12)),
            ]);
        }

        $workers = [
            ['employee_number' => 'EMP-NARVIK-001', 'first_name' => 'Ola', 'last_name' => 'Nordmann', 'phone' => '+4790000001', 'position' => 'courier', 'skills' => ['grocery-delivery', 'parcel-delivery'], 'zones' => ['narvik-sentrum', 'ankenes']],
            ['employee_number' => 'EMP-NARVIK-002', 'first_name' => 'Kari', 'last_name' => 'Nordmann', 'phone' => '+4790000002', 'position' => 'handyman', 'skills' => ['plumbing', 'furniture-assembly'], 'zones' => ['narvik-sentrum']],
            ['employee_number' => 'EMP-NARVIK-003', 'first_name' => 'Per', 'last_name' => 'Hansen', 'phone' => '+4790000003', 'position' => 'mover', 'skills' => ['apartment-moving', 'heavy-items'], 'zones' => ['narvik-sentrum', 'bjerkvik']],
            ['employee_number' => 'EMP-NARVIK-004', 'first_name' => 'Lena', 'last_name' => 'Olsen', 'phone' => '+4790000004', 'position' => 'tow', 'skills' => ['car-towing', 'roadside-assistance'], 'zones' => ['narvik-sentrum']],
            ['employee_number' => 'EMP-NARVIK-005', 'first_name' => 'Tom', 'last_name' => 'Berg', 'phone' => '+4790000005', 'position' => 'eco', 'skills' => ['furniture-disposal', 'recycling-pickup'], 'zones' => ['narvik-sentrum', 'ballangen']],
        ];

        foreach ($workers as $w) {
            $emp = Employee::updateOrCreate(
                ['employee_number' => $w['employee_number']],
                [
                    'user_id' => $defaultUser->id,
                    'first_name' => $w['first_name'],
                    'last_name' => $w['last_name'],
                    'phone' => $w['phone'],
                    'position' => $w['position'],
                    'status' => 'active',
                    'skills' => $w['skills'],
                    'metadata' => ['availability_zones' => $w['zones']],
                ]
            );

            $this->command->info("  ✓ Worker {$emp->employee_number} ({$emp->first_name}) upserted");
        }

        $this->command->info('✅ Narvik workers seeded.');
    }
}

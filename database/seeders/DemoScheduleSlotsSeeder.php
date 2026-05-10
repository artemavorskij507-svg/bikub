<?php

namespace Database\Seeders;

use App\Models\GeoZone;
use App\Models\ScheduleSlot;
use App\Models\ServiceType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoScheduleSlotsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo schedule slots...');

        $zone = GeoZone::where('slug', 'narvik-sentrum')->first();
        $service = ServiceType::where('slug', 'grocery-delivery')->first();

        if (! $zone || ! $service) {
            $this->command->warn('narvik-sentrum zone or grocery-delivery service not found, skipping schedule slots');

            return;
        }

        $slots = [
            [
                'name' => 'Morning Slot (08:00-12:00)',
                'start_at' => Carbon::today()->setHour(8)->setMinute(0),
                'end_at' => Carbon::today()->setHour(12)->setMinute(0),
                'capacity_total' => 10,
                'capacity_reserved' => 3,
                'capacity_confirmed' => 2,
                'status' => 'open',
                'kind' => 'delivery',
            ],
            [
                'name' => 'Afternoon Slot (12:00-17:00)',
                'start_at' => Carbon::today()->setHour(12)->setMinute(0),
                'end_at' => Carbon::today()->setHour(17)->setMinute(0),
                'capacity_total' => 15,
                'capacity_reserved' => 5,
                'capacity_confirmed' => 3,
                'status' => 'open',
                'kind' => 'delivery',
            ],
            [
                'name' => 'Evening Slot (17:00-21:00)',
                'start_at' => Carbon::today()->setHour(17)->setMinute(0),
                'end_at' => Carbon::today()->setHour(21)->setMinute(0),
                'capacity_total' => 8,
                'capacity_reserved' => 2,
                'capacity_confirmed' => 1,
                'status' => 'open',
                'kind' => 'delivery',
            ],
        ];

        foreach ($slots as $s) {
            ScheduleSlot::updateOrCreate(
                ['name' => $s['name'], 'start_at' => $s['start_at']],
                [
                    'zone_id' => $zone->id,
                    'service_type_id' => $service->id,
                    'kind' => $s['kind'],
                    'end_at' => $s['end_at'],
                    'capacity_total' => $s['capacity_total'],
                    'capacity_reserved' => $s['capacity_reserved'],
                    'capacity_confirmed' => $s['capacity_confirmed'],
                    'status' => $s['status'],
                    'hard_window' => true,
                    'buffer_before_min' => 15,
                    'buffer_after_min' => 10,
                ]
            );
            $this->command->info("  ✓ Schedule slot seeded: {$s['name']}");
        }

        $this->command->info('✅ Demo schedule slots seeded.');
    }
}

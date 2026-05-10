<?php

namespace Database\Seeders;

use App\Models\ScheduleSlot;
use Illuminate\Database\Seeder;

class ScheduleSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slots = [
            [
                'code' => 'morning',
                'name' => 'Morning Slot',
                'from' => '08:00',
                'to' => '12:00',
                'dow' => [1, 2, 3, 4, 5], // Monday to Friday
                'is_active' => true,
                'max_orders' => 20,
            ],
            [
                'code' => 'day',
                'name' => 'Day Slot',
                'from' => '12:00',
                'to' => '16:00',
                'dow' => [1, 2, 3, 4, 5],
                'is_active' => true,
                'max_orders' => 25,
            ],
            [
                'code' => 'evening',
                'name' => 'Evening Slot',
                'from' => '16:00',
                'to' => '20:00',
                'dow' => [1, 2, 3, 4, 5],
                'is_active' => true,
                'max_orders' => 15,
            ],
            [
                'code' => 'weekend_morning',
                'name' => 'Weekend Morning',
                'from' => '09:00',
                'to' => '13:00',
                'dow' => [6, 7], // Saturday and Sunday
                'is_active' => true,
                'max_orders' => 10,
            ],
            [
                'code' => 'weekend_afternoon',
                'name' => 'Weekend Afternoon',
                'from' => '13:00',
                'to' => '17:00',
                'dow' => [6, 7],
                'is_active' => true,
                'max_orders' => 10,
            ],
            [
                'code' => 'urgent',
                'name' => 'Urgent Slot',
                'from' => '00:00',
                'to' => '23:59',
                'dow' => [1, 2, 3, 4, 5, 6, 7], // All days
                'is_active' => true,
                'max_orders' => 5,
            ],
        ];

        foreach ($slots as $slot) {
            ScheduleSlot::updateOrCreate(
                ['code' => $slot['code']],
                $slot
            );
        }

        $this->command->info('Schedule slots created successfully!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\PartnerSettings;
use Illuminate\Database\Seeder;

class PartnerSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = Partner::all();

        foreach ($partners as $partner) {
            if (! $partner->settings) {
                PartnerSettings::create([
                    'partner_id' => $partner->id,
                    'notification_email' => null,
                    'timezone' => 'Europe/Kyiv',
                    'language' => 'uk',
                    'api_key' => hash('sha256', $partner->id.$partner->domain.now()),
                ]);
            }
        }

        $this->command->info('Partner settings seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\PartnerApiKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Demo Logistics';

        $attributes = [
            'type' => 'logistics',
            'active' => true,
            'webhook_url' => null,
        ];

        if (Schema::hasColumn('partners', 'webhook_secret')) {
            $attributes['webhook_secret'] = Str::random(32);
        }

        if (Schema::hasColumn('partners', 'slug')) {
            $attributes['slug'] = Str::slug($name);
        }

        $p = Partner::firstOrCreate(['name' => $name], $attributes);

        PartnerApiKey::firstOrCreate(['partner_id' => $p->id, 'prefix' => 'pk_live_'], [
            'key_hash' => bcrypt('demo_secret'),
            'is_active' => true,
            'rate_limit_per_min' => 120,
        ]);
    }
}

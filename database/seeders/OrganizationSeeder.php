<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Default Organization',
                'slug' => 'default-org',
                'description' => 'Default organization for GLF BiKube',
                'status' => 'active',
                'features' => [
                    'subscriptions' => true,
                    'returns_refunds' => true,
                    'reviews_disputes' => true,
                    'loyalty_program' => true,
                    'analytics' => true,
                    'multi_language' => true,
                ],
                'settings' => [
                    'timezone' => 'Europe/Oslo',
                    'currency' => 'NOK',
                    'locale' => 'no',
                ],
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Narvik Delivery Hub',
                'slug' => 'narvik-hub',
                'description' => 'Main delivery hub for Narvik region',
                'status' => 'active',
                'subdomain' => 'narvik',
                'features' => [
                    'subscriptions' => true,
                    'returns_refunds' => true,
                    'analytics' => true,
                ],
                'settings' => [
                    'timezone' => 'Europe/Oslo',
                    'currency' => 'NOK',
                ],
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Organization (Trial)',
                'slug' => 'test-org-trial',
                'description' => 'Test organization in trial period',
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'features' => [
                    'subscriptions' => false,
                    'analytics' => true,
                ],
                'settings' => [
                    'timezone' => 'Europe/Oslo',
                ],
            ],
        ];

        foreach ($organizations as $orgData) {
            $org = Organization::firstOrNew(['slug' => $orgData['slug']]);
            if (! $org->exists) {
                $org->fill($orgData);
            } else {
                $data = $orgData;
                unset($data['id']);
                $org->fill($data);
            }
            $org->save();

            // Attach first admin user to default organization
            if ($org->slug === 'default-org') {
                $admin = User::where('email', 'admin@example.com')
                    ->orWhereHas('roles', function ($q) {
                        $q->where('name', 'admin');
                    })
                    ->first();

                if ($admin && ! $org->users()->where('users.id', $admin->id)->exists()) {
                    DB::table('organization_users')->insert([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $org->id,
                        'user_id' => $admin->id,
                        'role' => 'admin',
                        'permissions' => json_encode(['*']),
                        'is_active' => true,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}

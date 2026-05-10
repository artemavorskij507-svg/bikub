<?php

namespace Database\Seeders;

use App\Models\CommunityPointsBalance;
use App\Models\CommunityPointsTransaction;
use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class SocialCommunityPointsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Social Community Points...');

        $pointsData = [
            ['user' => 'Eva Nystad', 'points' => 40],
            ['user' => 'Kari Stenersen', 'points' => 55],
            ['user' => 'Ida Moen', 'points' => 25],
        ];

        foreach ($pointsData as $data) {
            $user = User::where('name', $data['user'])->first();
            if (! $user) {
                $this->command->warn("  ⚠ User not found: {$data['user']}");

                continue;
            }

            $helperProfile = SocialHelperProfile::where('user_id', $user->id)->first();
            if (! $helperProfile) {
                $this->command->warn("  ⚠ SocialHelperProfile not found for: {$data['user']}");

                continue;
            }

            // Create or update balance
            $balance = CommunityPointsBalance::updateOrCreate(
                ['helper_profile_id' => $helperProfile->id],
                [
                    'balance_points' => $data['points'],
                    'lifetime_points' => $data['points'] + rand(10, 50), // Assume some were already spent
                ]
            );

            // Create transaction if balance was just created
            if ($balance->wasRecentlyCreated) {
                CommunityPointsTransaction::create([
                    'helper_profile_id' => $helperProfile->id,
                    'delta_points' => $data['points'],
                    'reason_code' => 'seeder_initial',
                    'meta' => [
                        'source' => 'social-community-points-seeder',
                        'description' => 'Initial points allocation',
                    ],
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }

            $this->command->info("  ✓ Created/Updated points for {$data['user']}: {$data['points']} points");
        }

        $this->command->info('✅ Social Community Points seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\SocialHelperProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class SocialCareTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Social Care Teams...');

        $teams = [
            [
                'team_name' => 'Care Team Narvik',
                'zone' => 'Narvik',
                'members' => ['Eva Nystad'],
            ],
            [
                'team_name' => 'Care Team Ankenes',
                'zone' => 'Ankenes',
                'members' => ['Kari Stenersen'],
            ],
            [
                'team_name' => 'Care Team Bjerkvik',
                'zone' => 'Bjerkvik',
                'members' => ['Ida Moen'],
            ],
        ];

        foreach ($teams as $teamData) {
            $memberUsers = [];
            foreach ($teamData['members'] as $memberName) {
                $user = User::where('name', $memberName)->first();
                if ($user) {
                    $memberUsers[] = $user;
                }
            }

            if (empty($memberUsers)) {
                $this->command->warn("  ⚠ No members found for team: {$teamData['team_name']}");

                continue;
            }

            // Store team info in helper profiles (using bio or notes field if available)
            // Since metadata doesn't exist, we'll just log the team info
            foreach ($memberUsers as $memberUser) {
                $helperProfile = SocialHelperProfile::where('user_id', $memberUser->id)->first();
                if ($helperProfile) {
                    // Update bio to include team info
                    $bio = $helperProfile->bio ?? '';
                    if (! str_contains($bio, $teamData['team_name'])) {
                        $helperProfile->bio = ($bio ? $bio.' | ' : '')."Team: {$teamData['team_name']} ({$teamData['zone']})";
                        $helperProfile->save();
                    }
                }
            }

            $this->command->info("  ✓ Created/Updated team: {$teamData['team_name']} with ".count($memberUsers).' member(s)');
        }

        $this->command->info('✅ Social Care Teams seeded successfully!');
    }
}

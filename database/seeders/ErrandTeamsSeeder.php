<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ErrandTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Errand Teams...');

        $teams = [
            [
                'team_name' => 'Errand Express Narvik',
                'zone' => 'Narvik',
                'members' => ['Liam Karlsen'],
            ],
            [
                'team_name' => 'Errand Express Ankenes',
                'zone' => 'Ankenes',
                'members' => ['Marlene Håkonsen'],
            ],
            [
                'team_name' => 'Errand Express Bjerkvik',
                'zone' => 'Bjerkvik',
                'members' => ['Sivert Mo'],
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

            // Store team info in executor profiles metadata
            foreach ($memberUsers as $memberUser) {
                $executorProfile = ExecutorProfile::where('user_id', $memberUser->id)->first();
                if ($executorProfile) {
                    $metadata = $executorProfile->metadata ?? [];
                    $metadata['team_name'] = $teamData['team_name'];
                    $metadata['team_zone'] = $teamData['zone'];
                    $metadata['team_members'] = collect($teamData['members'])->map(function ($name) {
                        return User::where('name', $name)->value('id');
                    })->filter()->values()->toArray();
                    $executorProfile->metadata = $metadata;
                    $executorProfile->save();
                }
            }

            $this->command->info("  ✓ Created/Updated team: {$teamData['team_name']} with ".count($memberUsers).' member(s)');
        }

        $this->command->info('✅ Errand Teams seeded successfully!');
    }
}

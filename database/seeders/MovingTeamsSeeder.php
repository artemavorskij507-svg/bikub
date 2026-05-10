<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\Moving\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MovingTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Narvik FlytteService Team A',
                'members_count' => 2,
                'vehicle' => 'VW Crafter LWB',
                'max_load_kg' => 1200,
                'region' => 'Narvik',
                'is_active' => true,
            ],
            [
                'name' => 'Narvik FlytteService Team B',
                'members_count' => 3,
                'vehicle' => 'Mercedes Sprinter XL',
                'max_load_kg' => 1500,
                'region' => 'Fagerneset / Narvik',
                'is_active' => true,
            ],
            [
                'name' => 'Bjerkvik Movers',
                'members_count' => 2,
                'vehicle' => 'Ford Transit Jumbo',
                'max_load_kg' => 1100,
                'region' => 'Bjerkvik',
                'is_active' => true,
            ],
            [
                'name' => 'Ballangen Transport Team',
                'members_count' => 2,
                'vehicle' => 'Renault Master L2H2',
                'max_load_kg' => 1000,
                'region' => 'Ballangen',
                'is_active' => true,
            ],
        ];

        foreach ($teams as $teamData) {
            $slug = Str::slug($teamData['name'], '.');
            $phoneTail = str_pad((string) (abs(crc32($slug)) % 10_000_000), 7, '0', STR_PAD_LEFT);
            $leader = User::firstOrCreate(
                ['email' => Str::slug($teamData['name'], '.').'@moving.glf.no'],
                [
                    'name' => $teamData['name'].' Lead',
                    'password' => 'moving-team-secret',
                    'phone' => '+47'.$phoneTail,
                    'locale' => 'no',
                    'is_active' => true,
                ]
            );

            $team = Team::updateOrCreate(
                ['name' => $teamData['name']],
                [
                    'description' => $teamData['region'].' • '.$teamData['vehicle'],
                    'leader_id' => $leader->id,
                    'status' => $teamData['is_active'] ? 'active' : 'inactive',
                    'max_orders' => max(2, $teamData['members_count'] * 2),
                    'rating' => 4.7,
                    'completed_orders_count' => 0,
                    'specializations' => ['moving', 'packing', 'takelage'],
                    'metadata' => [
                        'members_count' => $teamData['members_count'],
                        'vehicle' => $teamData['vehicle'],
                        'max_load_kg' => $teamData['max_load_kg'],
                        'region' => $teamData['region'],
                    ],
                ]
            );

            $executorsToAttach = [
                $leader->id => [
                    'role' => 'leader',
                    'joined_at' => now(),
                ],
            ];

            // Создать ExecutorProfile для лидера, если его нет
            ExecutorProfile::firstOrCreate(
                ['user_id' => $leader->id],
                [
                    'vehicle_type' => 'van',
                    'skills' => ['moving', 'packing', 'takelage'],
                    'max_volume' => 15,
                    'max_weight' => (int) ($teamData['max_load_kg'] * 0.6),
                    'insurance_limit' => 200000,
                    'rating' => 4.8,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'metadata' => [
                        'team' => $team->name,
                        'region' => $teamData['region'],
                    ],
                ]
            );

            // Добавить дополнительных участников команды
            for ($i = 1; $i < $teamData['members_count']; $i++) {
                $member = User::firstOrCreate(
                    ['email' => Str::slug($teamData['name'], '.').".member{$i}@moving.glf.no"],
                    [
                        'name' => $teamData['name']." Member {$i}",
                        'password' => 'moving-team-secret',
                        'phone' => '+47'.str_pad((string) (abs(crc32($slug.$i)) % 10_000_000), 7, '0', STR_PAD_LEFT),
                        'locale' => 'no',
                        'is_active' => true,
                    ]
                );

                ExecutorProfile::firstOrCreate(
                    ['user_id' => $member->id],
                    [
                        'vehicle_type' => 'van',
                        'skills' => ['moving', 'packing'],
                        'max_volume' => 12,
                        'max_weight' => (int) ($teamData['max_load_kg'] * 0.5),
                        'insurance_limit' => 150000,
                        'rating' => 4.6,
                        'completed_orders_count' => 0,
                        'is_active' => true,
                        'metadata' => [
                            'team' => $team->name,
                            'region' => $teamData['region'],
                        ],
                    ]
                );

                $executorsToAttach[$member->id] = [
                    'role' => 'member',
                    'joined_at' => now(),
                ];
            }

            $team->executors()->syncWithoutDetaching($executorsToAttach);
        }
    }
}

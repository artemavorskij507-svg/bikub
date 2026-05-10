<?php

namespace Database\Seeders;

use App\Models\Moving\ExecutorProfile;
use App\Models\RepairProject;
use App\Models\RepairTeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class HandymanTeamSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'name' => 'Andreas Johansen',
                'role' => 'Электрик',
                'skills_summary' => 'Установка светильников, щитки, розетки, защита от перегрузок',
                'skills' => ['lighting', 'breaker-box', 'sockets', 'safety'],
                'phone' => '+47 400 10 201',
                'email' => 'andreas.elec@bikube.no',
                'region' => 'Narvik +60km',
                'languages' => ['no', 'en'],
                'experience_years' => 7,
                'vehicle_type' => 'van',
                'rating' => 4.9,
                'projects' => [
                    [
                        'title' => 'Установка светильников и ламп',
                        'role' => 'Электрик / ведущий по свету',
                        'is_lead' => true,
                    ],
                    [
                        'title' => 'Установка кухонных приборов',
                        'role' => 'Электрик (подключение защиты)',
                        'is_lead' => false,
                    ],
                ],
            ],
            [
                'name' => 'Ole Kristian Nilsen',
                'role' => 'Сантехник',
                'skills_summary' => 'Подключение стиралок, посудомоек, устранение протечек, замена смесителей',
                'skills' => ['plumbing', 'washing-machines', 'dishwashers', 'leak-fix'],
                'phone' => '+47 400 10 202',
                'email' => 'ole.plumber@bikube.no',
                'region' => 'Narvik +60km',
                'languages' => ['no', 'en'],
                'experience_years' => 9,
                'vehicle_type' => 'van',
                'rating' => 4.8,
                'projects' => [
                    [
                        'title' => 'Установка стиральной машины',
                        'role' => 'Сантехник',
                        'is_lead' => true,
                    ],
                ],
            ],
            [
                'name' => 'Dmytro Yavorskyi',
                'role' => 'Универсальный мастер',
                'skills_summary' => 'Сборка мебели, мелкий ремонт, карнизы, полки, базовая электрика и сантехника',
                'skills' => ['furniture', 'minor-repair', 'mounting', 'basic-electric', 'basic-plumbing'],
                'phone' => '+47 400 10 203',
                'email' => 'dmytro.handyman@bikube.no',
                'region' => 'Narvik +60km',
                'languages' => ['ru', 'uk', 'en'],
                'experience_years' => 10,
                'vehicle_type' => 'van',
                'rating' => 4.95,
                'projects' => [
                    [
                        'title' => 'Сборка мебели IKEA / Jysk',
                        'role' => 'Лид по сборке мебели',
                        'is_lead' => true,
                    ],
                    [
                        'title' => 'Мелкий бытовой ремонт',
                        'role' => 'Универсальный мастер',
                        'is_lead' => true,
                    ],
                ],
            ],
            [
                'name' => 'Lena Berg',
                'role' => 'Монтаж кухонь и шкафов',
                'skills_summary' => 'Сборка кухонь IKEA/JYSK, регулировка фасадов, врезка техники',
                'skills' => ['kitchen', 'ikea', 'facade', 'appliance-mount'],
                'phone' => '+47 400 10 204',
                'email' => 'lena.kitchen@bikube.no',
                'region' => 'Narvik +60km',
                'languages' => ['no', 'en'],
                'experience_years' => 6,
                'vehicle_type' => 'van',
                'rating' => 4.85,
                'projects' => [
                    [
                        'title' => 'Установка кухонных приборов',
                        'role' => 'Специалист по кухне',
                        'is_lead' => true,
                    ],
                    [
                        'title' => 'Сборка мебели IKEA / Jysk',
                        'role' => 'Помощник по фасадам',
                        'is_lead' => false,
                    ],
                ],
            ],
            [
                'name' => 'Martin Solheim',
                'role' => 'Плотник / столяр',
                'skills_summary' => 'Двери, наличники, плинтуса, небольшие конструкции, ремонт мебели',
                'skills' => ['carpentry', 'doors', 'trim', 'furniture-fix'],
                'phone' => '+47 400 10 205',
                'email' => 'martin.carpenter@bikube.no',
                'region' => 'Narvik +60km',
                'languages' => ['no', 'en'],
                'experience_years' => 8,
                'vehicle_type' => 'van',
                'rating' => 4.9,
                'projects' => [
                    [
                        'title' => 'Ремонт и обслуживание дверей',
                        'role' => 'Плотник',
                        'is_lead' => true,
                    ],
                ],
            ],
        ];

        foreach ($members as $member) {
            $user = User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'phone' => $member['phone'],
                    'phone_e164' => $member['phone'],
                    'password' => $this->resolvePassword($member),
                    'locale' => Arr::first($member['languages']) ?? 'no',
                    'is_active' => true,
                    'preferences' => [
                        'region' => $member['region'],
                    ],
                ]
            );

            $profile = ExecutorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => $member['vehicle_type'],
                    'skills' => $member['skills'],
                    'max_volume' => 12,
                    'max_weight' => 800,
                    'insurance_limit' => 150000,
                    'rating' => $member['rating'],
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'metadata' => [
                        'role' => $member['role'],
                        'region' => $member['region'],
                        'languages' => $member['languages'],
                        'experience_years' => $member['experience_years'],
                        'skills_summary' => $member['skills_summary'],
                    ],
                ]
            );

            foreach ($member['projects'] as $projectConfig) {
                $project = RepairProject::where('title', $projectConfig['title'])->first();

                if (! $project) {
                    $this->command?->warn("Repair project '{$projectConfig['title']}' not found for {$member['name']}.");

                    continue;
                }

                RepairTeamMember::updateOrCreate(
                    [
                        'repair_project_id' => $project->id,
                        'executor_profile_id' => $profile->id,
                    ],
                    [
                        'role' => $projectConfig['role'] ?? $member['role'],
                        'is_lead' => $projectConfig['is_lead'] ?? false,
                        'notes' => $member['skills_summary'],
                    ]
                );
            }
        }
    }

    protected function resolvePassword(array $member): string
    {
        if (! empty($member['password'])) {
            return $member['password'];
        }

        return 'handyman-'.Str::slug($member['name'], '-');
    }
}

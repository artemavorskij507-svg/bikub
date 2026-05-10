<?php

namespace Database\Seeders;

use App\Models\HandymanAssignment;
use App\Models\Moving\ExecutorProfile;
use App\Models\RepairProject;
use App\Models\RepairStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HandymanAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $projects = RepairProject::whereIn('title', [
            'Установка стиральной машины',
            'Сборка мебели IKEA / Jysk',
            'Установка кухонных приборов',
        ])->get()->keyBy('title');

        $stages = RepairStage::whereIn('name', [
            'Диагностика',
            'Выполнение работ',
            'Уборка и финальный контроль',
        ])->get()->groupBy('repair_project_id');

        $profiles = ExecutorProfile::whereHas('user', function ($query) {
            $query->whereIn('email', [
                'andreas.elec@bikube.no',
                'ole.plumber@bikube.no',
                'dmytro.handyman@bikube.no',
                'lena.kitchen@bikube.no',
            ]);
        })
            ->with('user')
            ->get()
            ->keyBy(fn ($profile) => $profile->user?->email);

        $assignments = [
            [
                'project_title' => 'Установка стиральной машины',
                'executor_email' => 'ole.plumber@bikube.no',
                'stage_name' => 'Диагностика',
                'title' => 'Подключение стиральной машины в Narvik (Skistua)',
                'description' => 'Подключение новой стиралки, проверка на протечки, краткий инструктаж клиента',
                'status' => 'accepted',
                'workflow_status' => 'scheduled',
                'planned_start_at' => now()->addDays(1)->setTime(10, 0),
                'planned_finish_at' => now()->addDays(1)->setTime(11, 30),
                'region' => 'Narvik',
            ],
            [
                'project_title' => 'Сборка мебели IKEA / Jysk',
                'executor_email' => 'dmytro.handyman@bikube.no',
                'stage_name' => 'Выполнение работ',
                'title' => 'Сборка гардероба и комода IKEA (Fagerneset)',
                'description' => 'Сборка гардероба PAX и комода MALM, выравнивание по уровню',
                'status' => 'proposed',
                'workflow_status' => 'in_planning',
                'planned_start_at' => now()->addDays(2)->setTime(14, 0),
                'planned_finish_at' => now()->addDays(2)->setTime(17, 0),
                'region' => 'Narvik, Fagerneset',
            ],
            [
                'project_title' => 'Установка кухонных приборов',
                'executor_email' => 'lena.kitchen@bikube.no',
                'stage_name' => 'Выполнение работ',
                'title' => 'Монтаж кухонной техники и фасадов (Bjerkvik)',
                'description' => 'Установка варочной панели, духового шкафа и фасадов на верхние шкафчики',
                'status' => 'accepted',
                'workflow_status' => 'scheduled',
                'planned_start_at' => now()->addDays(3)->setTime(9, 30),
                'planned_finish_at' => now()->addDays(3)->setTime(12, 0),
                'region' => 'Bjerkvik',
            ],
            [
                'project_title' => 'Установка стиральной машины',
                'executor_email' => 'andreas.elec@bikube.no',
                'stage_name' => 'Уборка и финальный контроль',
                'title' => 'Финальная проверка и уборка после подключения стиралки',
                'description' => 'Проверить отсутствие протечек, убрать упаковку, подтвердить результат с клиентом',
                'status' => 'proposed',
                'workflow_status' => 'pending_followup',
                'planned_start_at' => now()->addDays(1)->setTime(12, 30),
                'planned_finish_at' => now()->addDays(1)->setTime(13, 30),
                'region' => 'Narvik',
            ],
        ];

        foreach ($assignments as $config) {
            $project = $projects->get($config['project_title']);
            if (! $project) {
                $this->command?->warn("Project '{$config['project_title']}' not found, skipping assignment '{$config['title']}'.");

                continue;
            }

            $stage = $stages->get($project->id)?->firstWhere('name', $config['stage_name']);
            if (! $stage) {
                $this->command?->warn("Stage '{$config['stage_name']}' not found for project '{$config['project_title']}'.");
            }

            $profile = $profiles->get($config['executor_email']);
            if (! $profile) {
                $this->command?->warn("Executor with email {$config['executor_email']} not found.");

                continue;
            }

            if (! $project->order_id) {
                $this->command?->warn("Project '{$config['project_title']}' missing linked order.");

                continue;
            }

            HandymanAssignment::updateOrCreate(
                [
                    'order_id' => $project->order_id,
                    'executor_profile_id' => $profile->id,
                    'planned_start_at' => $config['planned_start_at'],
                ],
                [
                    'repair_project_id' => $project->id,
                    'status' => $config['status'],
                    'planned_finish_at' => $config['planned_finish_at'],
                    'meta' => [
                        'title' => $config['title'],
                        'description' => $config['description'],
                        'region' => $config['region'],
                        'workflow_status' => $config['workflow_status'],
                        'stage_id' => $stage?->id,
                        'stage_name' => $stage?->name,
                        'slug' => Str::slug($config['title']),
                    ],
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\RepairProject;
use App\Models\RepairStage;
use Illuminate\Database\Seeder;

class RepairStageSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'name' => 'Диагностика',
                'order' => 1,
                'description' => 'Оценка задачи на месте, определение сложности',
            ],
            [
                'name' => 'Подготовка инструментов',
                'order' => 2,
                'description' => 'Подготовка оборудования и расходников',
            ],
            [
                'name' => 'Выполнение работ',
                'order' => 3,
                'description' => 'Основной этап ремонта или установки',
            ],
            [
                'name' => 'Тестирование',
                'order' => 4,
                'description' => 'Проверка корректной работы после выполнения',
            ],
            [
                'name' => 'Уборка и финальный контроль',
                'order' => 5,
                'description' => 'Приведение рабочего места в порядок и подтверждение результата',
            ],
        ];

        $projects = RepairProject::all();

        foreach ($projects as $project) {
            foreach ($definitions as $stage) {
                RepairStage::updateOrCreate(
                    [
                        'repair_project_id' => $project->id,
                        'sequence' => $stage['order'],
                    ],
                    [
                        'name' => $stage['name'],
                        'description' => $stage['description'],
                        'status' => 'planned',
                        'progress_percent' => 0,
                    ]
                );
            }
        }
    }
}

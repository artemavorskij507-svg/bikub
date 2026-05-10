<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\ClaimMessage;
use App\Models\Order;
use App\Models\RepairProject;
use App\Models\User;
use App\Services\Claims\ClaimSlaService;
use Illuminate\Database\Seeder;

class ClaimSeeder extends Seeder
{
    public function run(): void
    {
        // Отключаем Observer для ускорения
        Claim::unsetEventDispatcher();

        $users = User::has('orders')->take(5)->get();
        if ($users->isEmpty()) {
            $users = User::take(3)->get();
        }

        $orders = Order::whereNotNull('user_id')->without('user')->take(5)->get();
        if ($orders->isEmpty()) {
            // Создаем тестовые заказы если их нет
            $testUser = User::first();
            if (! $testUser) {
                $this->command->warn('Нет пользователей для создания претензий');

                return;
            }

            $order = Order::create([
                'user_id' => $testUser->id,
                'status' => 'completed',
                'total_amount' => 1000,
                'currency' => 'NOK',
            ]);
            $orders = collect([$order]);
        }

        // Используем пользователей из заказов, если они есть
        $orderUsers = $orders->pluck('user')->filter()->unique('id');
        if ($orderUsers->isNotEmpty()) {
            $users = $orderUsers;
        } elseif ($users->isEmpty()) {
            $users = User::take(5)->get();
        }

        $repairProjects = RepairProject::take(10)->get();

        $claimTypes = ['warranty', 'quality', 'delay', 'damage', 'other'];
        $statuses = ['open', 'in_progress', 'resolved', 'rejected'];
        $severities = ['low', 'medium', 'high', 'critical'];

        $titles = [
            'Некачественное выполнение работы',
            'Повреждение имущества при выполнении заказа',
            'Задержка выполнения заказа',
            'Мастер не приехал в назначенное время',
            'Несоответствие заявленным характеристикам',
            'Проблемы с гарантией',
            'Неудовлетворительное качество услуги',
            'Нарушение сроков выполнения',
        ];

        $descriptions = [
            'Работа была выполнена некачественно, требуется переделка.',
            'В процессе выполнения заказа было повреждено мое имущество.',
            'Заказ был выполнен с существенной задержкой от запланированного времени.',
            'Мастер не приехал в назначенное время, пришлось ждать несколько часов.',
            'Услуга не соответствует заявленным характеристикам и описанию.',
            'Возникли проблемы с гарантийным обслуживанием.',
            'Качество предоставленной услуги неудовлетворительное.',
            'Сроки выполнения были нарушены без предупреждения.',
        ];

        $slaService = app(ClaimSlaService::class);

        foreach ($orders->take(3) as $order) {
            $user = User::find($order->user_id) ?? $users->random();
            if (! $user) {
                continue;
            }
            $type = $claimTypes[array_rand($claimTypes)];
            $status = $statuses[array_rand($statuses)];
            $severity = $severities[array_rand($severities)];
            $title = $titles[array_rand($titles)];
            $description = $descriptions[array_rand($descriptions)];

            $claim = Claim::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'repair_project_id' => $repairProjects->isNotEmpty() ? $repairProjects->random()->id : null,
                'opened_by_user_id' => $user->id,
                'assigned_to_user_id' => null,
                'type' => $type,
                'status' => $status,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'resolution_notes' => $status === 'resolved' ? 'Претензия рассмотрена и решена.' : null,
                'resolution_type' => $status === 'resolved' ? 'refund' : null,
                'opened_at' => now()->subDays(rand(1, 30)),
                'resolved_at' => $status === 'resolved' ? now()->subDays(rand(1, 10)) : null,
                'responded_at' => $status !== 'open' ? now()->subDays(rand(1, 20)) : null,
                'meta' => [],
            ]);

            // Устанавливаем SLA через сервис
            $slaService->setInitialSla($claim);
            $slaService->updateSlaBreaches($claim);

            // Добавляем сообщения в чат (максимум 3)
            $messageCount = min(3, rand(1, 3));
            for ($i = 0; $i < $messageCount; $i++) {
                $isCustomer = $i % 2 === 0;
                ClaimMessage::create([
                    'claim_id' => $claim->id,
                    'sender_id' => $isCustomer ? $user->id : ($user->id), // Упростил для избежания загрузки отношений
                    'sender_role' => $isCustomer ? 'customer' : 'support',
                    'body' => $isCustomer
                        ? 'Здравствуйте, хотел бы уточнить статус моей претензии.'
                        : 'Добрый день! Мы рассмотрели вашу претензию и работаем над решением.',
                    'meta' => [],
                ]);
            }

            // Очищаем память
            unset($claim);
        }

        // Добавляем несколько претензий для Repair Projects
        foreach ($repairProjects->take(2) as $project) {
            if (! $project->order) {
                continue;
            }

            $user = $users->random();
            $claim = Claim::create([
                'user_id' => $user->id,
                'order_id' => $project->order->id,
                'repair_project_id' => $project->id,
                'opened_by_user_id' => $user->id,
                'type' => 'warranty',
                'status' => 'in_progress',
                'severity' => 'high',
                'title' => 'Проблемы с качеством ремонта',
                'description' => 'Обнаружены проблемы с качеством выполненных работ по ремонту.',
                'opened_at' => now()->subDays(rand(1, 15)),
                'responded_at' => now()->subDays(rand(1, 10)),
                'meta' => [],
            ]);

            // Устанавливаем SLA через сервис
            app(ClaimSlaService::class)->setInitialSla($claim);

            // Обновляем SLA breach статусы
            app(ClaimSlaService::class)->updateSlaBreaches($claim);

            ClaimMessage::create([
                'claim_id' => $claim->id,
                'sender_id' => $user->id,
                'sender_role' => 'customer',
                'body' => 'Обнаружил проблемы с качеством ремонта. Требуется исправление.',
                'meta' => [],
            ]);
        }

        $this->command->info('Создано '.Claim::count().' претензий с сообщениями');
    }
}

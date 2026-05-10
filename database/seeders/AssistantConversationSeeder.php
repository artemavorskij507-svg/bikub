<?php

namespace Database\Seeders;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssistantConversationSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create test users
        $admin = User::where('email', 'admin@example.com')->first();
        $courier = User::whereHas('roles', function ($q) {
            $q->where('name', 'courier');
        })->first();

        if (! $courier) {
            $courier = User::first();
        }

        if (! $admin) {
            $admin = User::first();
        }

        // Get a test order if exists
        $order = Order::first();

        // Ensure we have at least one user
        if (! $admin && ! $courier) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);
            $courier = $admin;
        }

        // Create conversations
        $conversations = [
            [
                'title' => 'Помощь с маршрутом доставки',
                'channel' => 'courier',
                'created_by' => $courier->id,
                'subject_type' => $order ? Order::class : User::class,
                'subject_id' => $order ? $order->id : $courier->id,
            ],
            [
                'title' => 'Вопрос по заказу #12345',
                'channel' => 'order',
                'created_by' => $admin->id,
                'subject_type' => $order ? Order::class : User::class,
                'subject_id' => $order ? $order->id : $admin->id,
            ],
            [
                'title' => 'Техническая поддержка',
                'channel' => 'support',
                'created_by' => $admin->id,
                'subject_type' => User::class,
                'subject_id' => $admin->id,
            ],
            [
                'title' => 'Административный запрос',
                'channel' => 'admin',
                'created_by' => $admin->id,
                'subject_type' => User::class,
                'subject_id' => $admin->id,
            ],
            [
                'title' => 'Оптимизация маршрута',
                'channel' => 'courier',
                'created_by' => $courier->id,
                'subject_type' => User::class,
                'subject_id' => $courier->id,
            ],
        ];

        foreach ($conversations as $convData) {
            $conversation = AssistantConversation::create($convData);

            // Add some messages to each conversation
            $messages = [
                [
                    'assistant_conversation_id' => $conversation->id,
                    'user_id' => $convData['created_by'],
                    'role' => 'user',
                    'content' => $this->getUserMessage($conversation->channel),
                    'from_ai' => false,
                ],
                [
                    'assistant_conversation_id' => $conversation->id,
                    'user_id' => null,
                    'role' => 'assistant',
                    'content' => $this->getAssistantReply($conversation->channel),
                    'from_ai' => true,
                ],
            ];

            foreach ($messages as $msgData) {
                AssistantMessage::create($msgData);
            }
        }
    }

    private function getUserMessage(string $channel): string
    {
        return match ($channel) {
            'courier' => 'Какой самый быстрый маршрут до адреса Kongens gate 15?',
            'order' => 'Где мой заказ? Когда он будет доставлен?',
            'support' => 'У меня проблема с приложением, не могу оформить заказ.',
            'admin' => 'Нужна статистика по заказам за сегодня.',
            default => 'Привет, нужна помощь.',
        };
    }

    private function getAssistantReply(string $channel): string
    {
        return match ($channel) {
            'courier' => 'Рекомендую маршрут через Storgata - там сейчас нет пробок. Время в пути: 12 минут. Не забудьте проверить адрес получателя.',
            'order' => 'Ваш заказ находится в пути. Ожидаемое время доставки: 14:30. Курьер уже в районе вашего адреса.',
            'support' => 'Проверьте, что у вас включен JavaScript и обновите страницу. Если проблема сохраняется, попробуйте очистить кеш браузера.',
            'admin' => 'За сегодня оформлено 47 заказов. Средний чек: 450 kr. Активных курьеров: 8.',
            default => 'Чем могу помочь?',
        };
    }
}

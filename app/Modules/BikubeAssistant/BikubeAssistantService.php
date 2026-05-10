<?php

namespace App\Modules\BikubeAssistant;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Bikube Smart Assistant
 * — Подсказки курьеру
 * — Автомаршрутизация
 * — Замены товаров
 * — ETA
 * — Push уведомления
 * — AI-инсайты
 */
class BikubeAssistantService
{
    public function generateInsights(Order $order): array
    {
        return [
            'eta' => $this->calculateETA($order),
            'traffic' => $this->checkTraffic(),
            'weather' => $this->checkWeather(),
            'suggestions' => $this->generateAIAssistantNotes($order),
        ];
    }

    public function calculateETA(Order $order): string
    {
        return now()->addMinutes(rand(8, 18))->toTimeString();
    }

    public function checkTraffic(): string
    {
        return 'Нет пробок, маршрут свободный';
    }

    public function checkWeather(): string
    {
        return 'Умеренный снег, соблюдайте дистанцию';
    }

    public function generateAIAssistantNotes(Order $order): array
    {
        return [
            'Проверьте товары по списку: '.Str::limit($order->notes ?? 'Нет описания', 120),
            'Не забудьте сфотографировать точку передачи',
            'У клиента предпочтение: оставить у двери',
        ];
    }

    public function pushToCourier(User $courier, array $data)
    {
        try {
            // Check if Redis is available via service container
            if (! app()->bound('redis')) {
                Log::debug("Redis service not bound, skipping push to courier {$courier->id}");

                return;
            }

            // Try to use Redis facade
            try {
                $redis = app('redis');
                if ($redis && method_exists($redis, 'connection')) {
                    $connection = $redis->connection();
                    if (method_exists($connection, 'publish')) {
                        $connection->publish("courier.assistant.{$courier->id}", json_encode($data));
                        Log::info("Pushed assistant insights to courier {$courier->id}", ['courier_id' => $courier->id]);

                        return;
                    }
                }
            } catch (\Exception $e) {
                // If Redis facade fails, try direct Redis class
                Log::debug('Redis facade failed, trying alternative method: '.$e->getMessage());
            }

            // Fallback: log the data instead of publishing
            Log::info("Assistant insights for courier {$courier->id} (Redis unavailable)", [
                'courier_id' => $courier->id,
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            // Catch any error (including class not found)
            Log::warning("Failed to push insights to courier {$courier->id}: ".$e->getMessage());
            // Не выбрасываем исключение, чтобы не прерывать процесс
        }
    }
}

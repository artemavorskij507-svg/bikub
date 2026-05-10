<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\WorkerStatus;
use App\Modules\BikubeAssistant\BikubeAssistantService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class AssistantDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?string $navigationLabel = 'Bikube Assistant';

    protected static ?string $title = 'Bikube Smart Assistant';

    protected static string $view = 'filament.pages.assistant-dashboard';

    public ?array $stats = null;

    public ?array $activeOrders = null;

    public bool $isBroadcasting = false;

    // Livewire polling interval (in seconds) - обновление каждые 15 секунд
    protected static ?string $pollingInterval = '15s';

    // Метод для обновления данных через polling
    public function refreshStats()
    {
        try {
            $this->loadData();
        } catch (\Exception $e) {
            \Log::error('Error in refreshStats: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            // Не выбрасываем исключение, чтобы не прерывать polling
        }
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        try {
            $assistant = new BikubeAssistantService;

            // Статистика - расширяем поиск заказов
            $totalActive = Order::whereIn('status', ['in_progress', 'delivering', 'confirmed', 'assigned'])
                ->whereNotNull('assigned_to')
                ->count();

            // Если нет активных заказов, показываем последние заказы для демонстрации
            $ordersQuery = Order::whereIn('status', ['in_progress', 'delivering', 'confirmed', 'assigned', 'pending'])
                ->whereNotNull('assigned_to')
                ->with(['assignedUser' => function ($query) {
                    $query->select('id', 'name', 'email');
                }])
                ->orderBy('created_at', 'desc')
                ->limit(10);

            $orders = $ordersQuery->get();

            $withInsights = $orders->map(function ($order) use ($assistant) {
                try {
                    if (! $order || ! $order->exists) {
                        return null;
                    }
                    $insights = $assistant->generateInsights($order);

                    // Сохраняем только необходимые данные для избежания проблем с сериализацией
                    $orderData = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'assigned_to' => $order->assigned_to,
                        'created_at' => $order->created_at?->toDateTimeString(),
                        'assigned_user' => $order->assignedUser ? [
                            'id' => $order->assignedUser->id,
                            'name' => $order->assignedUser->name,
                            'email' => $order->assignedUser->email,
                        ] : null,
                    ];

                    return [
                        'order' => $orderData,
                        'order_model' => $order, // Для доступа в шаблоне, но не сериализуется
                        'insights' => $insights,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error generating insights for order '.($order->id ?? 'unknown').': '.$e->getMessage());
                    // Return order with default insights
                    $orderData = [
                        'id' => $order->id ?? null,
                        'order_number' => $order->order_number ?? 'N/A',
                        'status' => $order->status ?? 'unknown',
                        'assigned_to' => $order->assigned_to ?? null,
                        'created_at' => $order->created_at?->toDateTimeString(),
                        'assigned_user' => $order->assignedUser ? [
                            'id' => $order->assignedUser->id,
                            'name' => $order->assignedUser->name,
                            'email' => $order->assignedUser->email,
                        ] : null,
                    ];

                    return [
                        'order' => $orderData,
                        'order_model' => $order,
                        'insights' => [
                            'eta' => 'N/A',
                            'traffic' => 'Данные недоступны',
                            'weather' => 'Данные недоступны',
                            'suggestions' => ['Обработка данных...'],
                        ],
                    ];
                }
            })->filter();

            // Count online couriers from WorkerStatus
            // Используем более надежный способ: получаем все онлайн-статусы и фильтруем по ролям
            try {
                $onlineWorkers = WorkerStatus::where('is_online', true)
                    ->with(['user' => function ($query) {
                        $query->with('roles');
                    }])
                    ->get();

                // Фильтруем только курьеров и исполнителей используя метод hasAnyRole
                $couriersOnline = $onlineWorkers->filter(function ($workerStatus) {
                    if (! $workerStatus->user) {
                        return false;
                    }
                    try {
                        // Используем метод hasAnyRole для проверки ролей
                        return $workerStatus->user->hasAnyRole(['courier', 'executor']);
                    } catch (\Exception $e) {
                        \Log::warning('Error checking role for user '.$workerStatus->user->id.': '.$e->getMessage());

                        return false;
                    }
                })->count();
            } catch (\Exception $e) {
                \Log::error('Error counting online couriers: '.$e->getMessage());
                $couriersOnline = 0;
            }

            // Получаем количество отправленных оповещений из кеша
            $totalInsightsSent = cache()->get('assistant:total_insights_sent', 0);

            $this->stats = [
                'active_orders' => $totalActive > 0 ? $totalActive : $orders->count(),
                'couriers_online' => $couriersOnline ?? 0,
                'total_insights_sent' => $totalInsightsSent ?? 0,
            ];

            // Сохраняем данные для Livewire - только массивы, без моделей
            // Преобразуем все в чистые массивы для избежания проблем с сериализацией
            $this->activeOrders = $withInsights->values()->map(function ($item) {
                // Убираем order_model из данных, которые будут сериализованы
                if (isset($item['order_model'])) {
                    unset($item['order_model']);
                }
                // Убеждаемся, что order - это массив, а не объект
                if (isset($item['order']) && is_object($item['order'])) {
                    $item['order'] = (array) $item['order'];
                }
                // Убеждаемся, что все вложенные данные - массивы
                if (isset($item['order']['assigned_user']) && is_object($item['order']['assigned_user'])) {
                    $item['order']['assigned_user'] = (array) $item['order']['assigned_user'];
                }

                return $item;
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Error loading assistant dashboard data: '.$e->getMessage());
            $this->stats = [
                'active_orders' => 0,
                'couriers_online' => 0,
                'total_insights_sent' => 0,
            ];
            $this->activeOrders = [];
        }
    }

    public function broadcastInsights(): void
    {
        $this->isBroadcasting = true;

        try {
            $ordersCount = Order::whereIn('status', ['in_progress', 'delivering', 'confirmed', 'assigned'])
                ->whereNotNull('assigned_to')
                ->count();

            if ($ordersCount > 0) {
                $result = Artisan::call('assistant:broadcast');
                $output = Artisan::output();

                // Извлекаем количество отправленных из вывода команды
                preg_match('/(\d+)\s+orders/i', $output, $matches);
                $sentCount = $matches[1] ?? $ordersCount;

                // Обновляем счетчик отправленных оповещений в кеше
                $currentTotal = cache()->get('assistant:total_insights_sent', 0);
                cache()->put('assistant:total_insights_sent', $currentTotal + (int) $sentCount, now()->addDays(30));

                Notification::make()
                    ->title('Подсказки отправлены')
                    ->success()
                    ->body("Подсказки успешно отправлены для {$sentCount} заказов")
                    ->send();
            } else {
                Notification::make()
                    ->title('Нет активных заказов')
                    ->warning()
                    ->body('Нет заказов со статусом "in_progress" или "delivering" с назначенными курьерами')
                    ->send();
            }

            $this->loadData();
        } catch (\Exception $e) {
            \Log::error('Error broadcasting insights: '.$e->getMessage(), ['exception' => $e]);
            Notification::make()
                ->title('Ошибка')
                ->danger()
                ->body('Ошибка при отправке: '.$e->getMessage())
                ->send();
        } finally {
            $this->isBroadcasting = false;
        }
    }
}

<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkerPayoutRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Общая сумма заработанного: сумма payout_amount из Task для завершенных заказов пользователя
        // Используем Task через Order, где order->assigned_to = user->id и order->status = completed
        // Также учитываем Task с type = 'roadside_job', где executor_user_id в meta = user->id
        try {
            $totalEarned = Task::join('orders', 'tasks.order_id', '=', 'orders.id')
                ->where(function ($q) use ($user) {
                    $q->where('orders.assigned_to', $user->id)
                        ->orWhere(function ($sq) use ($user) {
                            // Для Roadside jobs проверяем executor_user_id в meta
                            // Используем JSON_EXTRACT для MySQL, но безопасно
                            $sq->where('tasks.type', 'roadside_job')
                                ->whereNotNull('tasks.meta')
                                ->whereRaw("JSON_EXTRACT(tasks.meta, '$.executor_user_id') = ?", [$user->id]);
                        });
                })
                ->whereIn('orders.status', ['completed', 'delivered'])
                ->where('tasks.status', 'completed')
                ->whereNotNull('tasks.payout_amount')
                ->sum('tasks.payout_amount');
        } catch (\Throwable $e) {
            // Если JSON_EXTRACT не работает, используем простой запрос
            Log::warning('WalletController: JSON_EXTRACT error', ['error' => $e->getMessage()]);
            $totalEarned = Task::join('orders', 'tasks.order_id', '=', 'orders.id')
                ->where('orders.assigned_to', $user->id)
                ->whereIn('orders.status', ['completed', 'delivered'])
                ->where('tasks.status', 'completed')
                ->whereNotNull('tasks.payout_amount')
                ->sum('tasks.payout_amount');
        }

        // Если payout_amount в Task пустой, используем альтернативный расчет через metadata Order
        // Включаем заказы, где user назначен напрямую или через Roadside job
        if ($totalEarned == 0) {
            try {
                // TODO: Если в будущем будет поле executor_fee в Order, использовать его
                // Пока используем metadata или 0
                $totalEarned = Order::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                        ->orWhereHas('roadsideEmergency', function ($req) use ($user) {
                            // Для Roadside: проверяем через helper или order->assigned_to
                            $req->whereHas('helper', function ($hq) use ($user) {
                                $hq->where('user_id', $user->id);
                            });
                        });
                })
                    ->whereIn('status', ['completed', 'delivered'])
                    ->get()
                    ->sum(function ($order) {
                        // Пытаемся получить executor_payout из metadata
                        return $order->metadata['executor_payout'] ?? 0;
                    });
            } catch (\Throwable $e) {
                Log::warning('WalletController: alternative totalEarned calculation error', ['error' => $e->getMessage()]);
                $totalEarned = 0;
            }
        }

        // Сумма всех выплаченных денег
        $totalPaid = Payout::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('amount');

        // Сумма заявок на выплату в обработке
        $pendingPayouts = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        // Доступно для выплаты
        $availableForPayout = max(0, $totalEarned - $totalPaid - $pendingPayouts);

        // Последние 10 завершенных заказов с суммой вознаграждения
        // Включаем заказы, где user назначен напрямую или через Roadside job
        try {
            $recentOrders = Order::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereHas('tasks', function ($tq) use ($user) {
                        // Для Roadside jobs проверяем executor_user_id в meta
                        $tq->where('type', 'roadside_job')
                            ->where('status', 'completed')
                            ->whereNotNull('meta')
                            ->whereRaw("JSON_EXTRACT(meta, '$.executor_user_id') = ?", [$user->id]);
                    });
            })
                ->whereIn('status', ['completed', 'delivered'])
                ->with(['orderItems.serviceType', 'address', 'roadsideEmergency'])
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    // Получаем payout_amount из Task для этого заказа
                    $payoutAmount = Task::where('order_id', $order->id)
                        ->where('status', 'completed')
                        ->whereNotNull('payout_amount')
                        ->sum('payout_amount');

                    // Если нет в Task, пытаемся из metadata
                    if ($payoutAmount == 0) {
                        $payoutAmount = $order->metadata['executor_payout'] ?? 0;
                    }

                    return [
                        'order' => $order,
                        'payout_amount' => $payoutAmount,
                    ];
                });
        } catch (\Throwable $e) {
            // Если JSON_EXTRACT не работает, используем простой запрос
            Log::warning('WalletController: recentOrders JSON_EXTRACT error', ['error' => $e->getMessage()]);
            $recentOrders = Order::where('assigned_to', $user->id)
                ->whereIn('status', ['completed', 'delivered'])
                ->with(['orderItems.serviceType', 'address', 'roadsideEmergency'])
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    $payoutAmount = Task::where('order_id', $order->id)
                        ->where('status', 'completed')
                        ->whereNotNull('payout_amount')
                        ->sum('payout_amount');

                    if ($payoutAmount == 0) {
                        $payoutAmount = $order->metadata['executor_payout'] ?? 0;
                    }

                    return [
                        'order' => $order,
                        'payout_amount' => $payoutAmount,
                    ];
                });
        }

        // Последние 10 выплат/заявок
        $payouts = Payout::where('user_id', $user->id)
            ->with('processedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('lk.wallet', [
            'user' => $user,
            'totalEarned' => $totalEarned,
            'totalPaid' => $totalPaid,
            'pendingPayouts' => $pendingPayouts,
            'availableForPayout' => $availableForPayout,
            'recentOrders' => $recentOrders,
            'payouts' => $payouts,
        ]);
    }

    /**
     * Request a payout.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'string', 'in:vipps,bank,cash'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Пересчитываем availableForPayout
        try {
            $totalEarned = Task::join('orders', 'tasks.order_id', '=', 'orders.id')
                ->where(function ($q) use ($user) {
                    $q->where('orders.assigned_to', $user->id)
                        ->orWhere(function ($sq) use ($user) {
                            // Для Roadside jobs проверяем executor_user_id в meta
                            $sq->where('tasks.type', 'roadside_job')
                                ->whereNotNull('tasks.meta')
                                ->whereRaw("JSON_EXTRACT(tasks.meta, '$.executor_user_id') = ?", [$user->id]);
                        });
                })
                ->whereIn('orders.status', ['completed', 'delivered'])
                ->where('tasks.status', 'completed')
                ->whereNotNull('tasks.payout_amount')
                ->sum('tasks.payout_amount');
        } catch (\Throwable $e) {
            // Если JSON_EXTRACT не работает, используем простой запрос
            Log::warning('WalletController: requestPayout JSON_EXTRACT error', ['error' => $e->getMessage()]);
            $totalEarned = Task::join('orders', 'tasks.order_id', '=', 'orders.id')
                ->where('orders.assigned_to', $user->id)
                ->whereIn('orders.status', ['completed', 'delivered'])
                ->where('tasks.status', 'completed')
                ->whereNotNull('tasks.payout_amount')
                ->sum('tasks.payout_amount');
        }

        if ($totalEarned == 0) {
            try {
                $totalEarned = Order::where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                        ->orWhereHas('roadsideEmergency', function ($req) use ($user) {
                            // Для Roadside: проверяем через helper или order->assigned_to
                            $req->whereHas('helper', function ($hq) use ($user) {
                                $hq->where('user_id', $user->id);
                            });
                        });
                })
                    ->whereIn('status', ['completed', 'delivered'])
                    ->get()
                    ->sum(function ($order) {
                        return $order->metadata['executor_payout'] ?? 0;
                    });
            } catch (\Throwable $e) {
                Log::warning('WalletController: requestPayout alternative totalEarned calculation error', ['error' => $e->getMessage()]);
                $totalEarned = 0;
            }
        }

        $totalPaid = Payout::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('amount');

        $pendingPayouts = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        $availableForPayout = max(0, $totalEarned - $totalPaid - $pendingPayouts);

        // Валидация суммы
        $amount = (float) $request->input('amount');

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Сумма должна быть больше нуля',
            ], 400);
        }

        if ($amount > $availableForPayout) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно средств. Доступно: '.number_format($availableForPayout, 2, ',', ' ').' kr',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Создаем заявку на выплату
            $payout = Payout::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => 'NOK',
                'status' => 'pending',
                'method' => $request->input('method', 'bank'),
                'note' => $request->input('note'),
            ]);

            DB::commit();

            // Отправляем уведомления админам
            $admins = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'operator', 'accountant']))
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new WorkerPayoutRequested($payout));
            }

            // Очищаем кеш, если используется
            cache()->forget("user_{$user->id}_wallet_data");

            return response()->json([
                'success' => true,
                'message' => 'Запрос на выплату создан',
                'payout' => $payout,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заявки: '.$e->getMessage(),
            ], 500);
        }
    }
}

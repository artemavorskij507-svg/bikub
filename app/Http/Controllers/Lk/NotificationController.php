<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Получаем уведомления (безопасно, если таблицы нет)
        $unreadNotifications = collect();
        $readNotifications = collect();

        if (Schema::hasTable('notifications')) {
            $unreadNotifications = $user->unreadNotifications()
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            $readNotifications = $user->readNotifications()
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        return view('lk.notifications', [
            'user' => $user,
            'unreadNotifications' => $unreadNotifications,
            'readNotifications' => $readNotifications,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Schema::hasTable('notifications')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Таблица уведомлений не найдена',
                ], 404);
            }

            return redirect()->back()->with('error', 'Таблица уведомлений не найдена');
        }

        /** @var \Illuminate\Notifications\DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($id)->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
            // Очищаем кеш для badge
            cache()->forget("user_{$user->id}_unread_notifications_count");
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Уведомление отмечено как прочитанное',
            ]);
        }

        return redirect()->back()->with('success', 'Уведомление отмечено как прочитанное');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Schema::hasTable('notifications')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Таблица уведомлений не найдена',
                ], 404);
            }

            return redirect()->back()->with('error', 'Таблица уведомлений не найдена');
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        // Очищаем кеш для badge
        cache()->forget("user_{$user->id}_unread_notifications_count");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Все уведомления отмечены как прочитанные',
            ]);
        }

        return redirect()->back()->with('success', 'Все уведомления отмечены как прочитанные');
    }

    /**
     * Bulk delete notifications.
     */
    public function bulkDelete(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Schema::hasTable('notifications')) {
            return response()->json([
                'success' => false,
                'message' => 'Таблица уведомлений не найдена',
            ], 404);
        }

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Не выбраны уведомления для удаления',
            ], 400);
        }

        $user->notifications()->whereKey($ids)->delete();

        // Очищаем кеш для badge
        cache()->forget("user_{$user->id}_unread_notifications_count");

        return response()->json([
            'success' => true,
            'message' => 'Уведомления удалены',
        ]);
    }
}

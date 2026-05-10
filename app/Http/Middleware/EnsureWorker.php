<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorker
{
    public function handle(Request $request, Closure $next): Response
    {
        // Диагностическое логирование в начале
        Log::info('EnsureWorker: начало обработки', [
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'env' => app()->environment(),
            'is_local' => app()->environment('local'),
            'user_authenticated' => auth()->check(),
        ]);

        $user = $request->user();

        // 1) Неавторизованный — на логин
        if (! $user) {
            Log::info('EnsureWorker: пользователь не авторизован, редирект на login');

            return redirect()->route('login');
        }

        // 2) Локальная среда (local): разрешаем ВСЕМ авторизованным,
        // чтобы можно было тестировать ЛК, пока роли настраиваются.
        if (app()->environment('local')) {
            // Временное логирование для диагностики
            Log::info('EnsureWorker: пропускаем пользователя на local', [
                'user_id' => $user->id,
                'email' => $user->email,
                'path' => $request->path(),
                'route_name' => $request->route()?->getName(),
                'env' => app()->environment(),
            ]);

            return $next($request);
        }

        // 3) БОЕВАЯ логика: доступ только "рабочим" ролям
        // Роли в базе данных хранятся в нижнем регистре (admin, courier, operator)
        $workerRoles = [
            'admin',           // даём доступ администратору
            'operator',        // оператор также может быть рабочим
            'courier',
            'dispatcher',      // если такая роль есть
            'executor',
            'eco_executor',
            'roadside_assist',
            'social_helper',
        ];

        // ВАРИАНТ 1 — если у User есть метод hasAnyRole()
        if (method_exists($user, 'hasAnyRole')) {
            // Получаем активные роли пользователя для логирования
            $userRoles = $user->roles()->where('is_active', true)->pluck('name')->toArray();

            // Логирование для диагностики
            Log::info('EnsureWorker: проверка ролей', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_roles' => $userRoles,
                'worker_roles' => $workerRoles,
                'hasAnyRole' => $user->hasAnyRole($workerRoles),
                'path' => $request->path(),
            ]);

            if (! $user->hasAnyRole($workerRoles)) {
                // TODO: когда будет готов ЛК клиента — перенаправить туда
                return redirect()->to('/');
            }

            return $next($request);
        }

        // На всякий случай по умолчанию — закрываем доступ
        Log::warning('EnsureWorker: метод hasAnyRole не найден', [
            'user_id' => $user->id,
            'email' => $user->email,
            'path' => $request->path(),
        ]);

        return redirect()->to('/');
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Moving\ExecutorProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsExecutor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Только активные исполнители могут использовать этот endpoint.');
        }

        $profile = $user->executorProfile;

        // Автоподключение профиля исполнителя для пользователей с соответствующей ролью
        $canAutoProvision = $user->hasAnyRole([
            'executor',
            'courier',
            'roadside_assist',
            'eco_executor',
            'admin',
            'operator',
        ]);

        if (! $profile && $canAutoProvision) {
            $profile = ExecutorProfile::create([
                'user_id' => $user->id,
                'vehicle_type' => 'van',
                'skills' => ['delivery', 'handyman', 'roadside'],
                'max_volume' => 12,
                'max_weight' => 800,
                'insurance_limit' => 100000,
                'rating' => 5.0,
                'completed_orders_count' => 0,
                'is_active' => true,
                'last_active_at' => now(),
                'metadata' => ['auto_provisioned' => true],
            ]);
        }

        if ($profile && ! $profile->is_active && $canAutoProvision) {
            $profile->forceFill([
                'is_active' => true,
                'last_active_at' => now(),
            ])->save();
        }

        if (! $profile || ! $profile->is_active) {
            abort(403, 'Только активные исполнители могут использовать этот endpoint.');
        }

        // Обновим last_active_at
        $profile->forceFill([
            'last_active_at' => now(),
        ])->save();

        return $next($request);
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Sentry will automatically capture exceptions via its service provider
            // But we can add custom context here if needed
            if (app()->bound('sentry')) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                    // Add user context
                    if (auth()->check()) {
                        $user = auth()->user();
                        $scope->setUser([
                            'id' => $user->id,
                            'email' => $user->email,
                            'name' => $user->name,
                        ]);
                    }

                    // Add request context (zone, order, task)
                    if (request()->has('zone_id')) {
                        $scope->setTag('zone_id', request()->input('zone_id'));
                    }
                    if (request()->has('order_id')) {
                        $scope->setTag('order_id', request()->input('order_id'));
                    }
                    if (request()->has('task_id')) {
                        $scope->setTag('task_id', request()->input('task_id'));
                    }
                    if (request()->has('slot_id')) {
                        $scope->setTag('slot_id', request()->input('slot_id'));
                    }
                });
            }
        });
    }

    /**
     * Handle unauthenticated users.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($this->shouldReturnJson($request, $exception)) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        // Редирект на Filament login, если это админка
        if ($request->is('admin*') || $request->is('filament*')) {
            return redirect()->guest('/admin/login');
        }

        // Для публичных маршрутов - на /login (который редиректит на /admin/login)
        return redirect()->guest('/login');
    }
}

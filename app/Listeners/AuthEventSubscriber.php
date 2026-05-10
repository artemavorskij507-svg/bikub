<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthEventSubscriber
{
    protected AuditLogger $logger;

    public function __construct()
    {
        $this->logger = app(AuditLogger::class);
    }

    public function handleLogin(Login $event)
    {
        $this->logger->log('login', 'user', $event->user->id, null, null, request());
    }

    public function handleLogout(Logout $event)
    {
        $this->logger->log('logout', 'user', $event->user->id, null, null, request());
    }

    public function handleFailed(Failed $event)
    {
        $userId = $event->user?->id ?? null;
        $this->logger->log('login_failed', 'user', $userId, null, null, request());
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events)
    {
        $events->listen(Login::class, [AuthEventSubscriber::class, 'handleLogin']);
        $events->listen(Logout::class, [AuthEventSubscriber::class, 'handleLogout']);
        $events->listen(Failed::class, [AuthEventSubscriber::class, 'handleFailed']);
    }
}

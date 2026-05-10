<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UserSessionService
{
    private ?bool $sessionTableExists = null;

    public function register(User $user, Request $request): void
    {
        if (! $this->canPersistSessions()) {
            return;
        }

        $this->runSessionWrite(function () use ($user, $request): void {
            UserSession::updateOrCreate(
                ['session_id' => $request->session()->getId()],
                [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'last_activity' => now(),
                ]
            );
        });
    }

    public function touch(User $user, Request $request): void
    {
        if (! $this->canPersistSessions()) {
            return;
        }

        $sessionId = $request->session()->getId();

        if (! $sessionId) {
            return;
        }

        $this->runSessionWrite(function () use ($sessionId, $user, $request): void {
            UserSession::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->update([
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'last_activity' => now(),
                ]);
        });
    }

    public function deleteCurrent(Request $request): void
    {
        if (! $this->canPersistSessions()) {
            return;
        }

        if ($sessionId = $request->session()->getId()) {
            $this->runSessionWrite(function () use ($sessionId): void {
                UserSession::where('session_id', $sessionId)->delete();
            });
        }
    }

    public function deleteOtherSessions(User $user, string $exceptSessionId): void
    {
        if (! $this->canPersistSessions()) {
            return;
        }

        $this->runSessionWrite(function () use ($user, $exceptSessionId): void {
            UserSession::where('user_id', $user->id)
                ->where('session_id', '!=', $exceptSessionId)
                ->delete();
        });
    }

    private function canPersistSessions(): bool
    {
        if ($this->sessionTableExists === null) {
            $this->sessionTableExists = Schema::hasTable('account_user_sessions');
        }

        return $this->sessionTableExists;
    }

    private function runSessionWrite(callable $operation): void
    {
        try {
            $operation();
        } catch (QueryException $e) {
            if ($this->isReadOnlyDatabaseError($e)) {
                // Degrade gracefully when DB is mounted as read-only in local/dev containers.
                $this->sessionTableExists = false;
                Log::warning('User session persistence disabled: database is read-only.');
                return;
            }

            throw $e;
        }
    }

    private function isReadOnlyDatabaseError(QueryException $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'readonly database')
            || str_contains($message, 'attempt to write a readonly database')
            || str_contains($message, 'read-only');
    }
}

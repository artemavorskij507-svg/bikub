<?php

namespace App\Services\Account;

use App\Models\ClientProfile;
use App\Models\User;

class AccountContextManager
{
    protected const SESSION_KEY = 'account.active_client_profile_id';

    public function getActiveClient(User $user): ?ClientProfile
    {
        $id = $this->sessionGet(self::SESSION_KEY);

        if (! $id) {
            return null;
        }

        $client = ClientProfile::find($id);

        if (! $client || ! $this->userCanActForClient($user, $client)) {
            $this->clear();

            return null;
        }

        return $client;
    }

    public function setActiveClient(User $user, ?ClientProfile $client): void
    {
        if (! $client) {
            $this->clear();

            return;
        }

        if (! $this->userCanActForClient($user, $client)) {
            throw new \RuntimeException('User is not allowed to act for this client.');
        }

        $this->sessionPut(self::SESSION_KEY, $client->id);
    }

    public function clear(): void
    {
        $this->sessionForget(self::SESSION_KEY);
    }

    public function userCanActForClient(User $user, ClientProfile $client): bool
    {
        if ($client->user_id === $user->id) {
            return true;
        }

        return $client->trustedContacts()
            ->where('user_id', $user->id)
            ->exists();
    }

    protected function sessionGet(string $key)
    {
        if (! app()->bound('session')) {
            return null;
        }

        try {
            return session()->get($key);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    protected function sessionPut(string $key, mixed $value): void
    {
        if (! app()->bound('session')) {
            return;
        }

        try {
            session()->put($key, $value);
        } catch (\RuntimeException $e) {
        }
    }

    protected function sessionForget(string $key): void
    {
        if (! app()->bound('session')) {
            return;
        }

        try {
            session()->forget($key);
        } catch (\RuntimeException $e) {
        }
    }
}

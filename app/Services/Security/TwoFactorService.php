<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(
        protected Google2FA $google2fa = new Google2FA
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function makeQrUri(User $user, string $secret): string
    {
        $appName = config('app.name', 'GLF Bikube');

        return $this->google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret
        );
    }

    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }
}

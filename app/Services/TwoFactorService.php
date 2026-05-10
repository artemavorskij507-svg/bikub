<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserTwoFactorSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new 2FA secret and return QR code data URL.
     * Does not save to DB yet.
     *
     * @gdpr-critical: generates temporary secret for authentication
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    /**
     * Verify TOTP code against secret (plaintext temporarily in memory).
     */
    public function verifyCode(string $secret, string $code): bool
    {
        try {
            return $this->google2fa->verifyKey($secret, $code);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate recovery codes (8–10 random 8-char codes).
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(8);
        }

        return $codes;
    }

    /**
     * Enable 2FA: save encrypted secret, recovery codes, and timestamps.
     *
     * @gdpr-critical: encrypts and stores secret + recovery codes
     */
    public function enable(User $user, string $plainSecret, array $recoveryCodes): UserTwoFactorSetting
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('user_two_factor_settings')) {
                throw new \Exception('Table user_two_factor_settings does not exist');
            }
            $setting = UserTwoFactorSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'secret' => Crypt::encryptString($plainSecret),
                    'recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
                    'enabled_at' => now(),
                    'confirmed_at' => now(),
                ]
            );

            // Log to audit
            app(AuditLogger::class)->log(
                'two_factor_enabled',
                User::class,
                $user->id,
                null,
                ['enabled_at' => now()]
            );

            return $setting;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('user_two_factor_settings')) {
                throw new \Exception('Table user_two_factor_settings does not exist');
            }
            UserTwoFactorSetting::where('user_id', $user->id)->delete();

            app(AuditLogger::class)->log(
                'two_factor_disabled',
                User::class,
                $user->id,
                null,
                ['disabled_at' => now()]
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check if 2FA is enabled and confirmed for user.
     */
    public function isConfirmed(User $user): bool
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('user_two_factor_settings')) {
                return false;
            }
            $setting = UserTwoFactorSetting::where('user_id', $user->id)->first();

            return $setting && $setting->confirmed_at !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get decrypted secret for verification (temporary, in memory only).
     * Do not log the decrypted secret.
     */
    public function getDecryptedSecret(User $user): ?string
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('user_two_factor_settings')) {
                return null;
            }
            $setting = UserTwoFactorSetting::where('user_id', $user->id)->first();
            if (! $setting || ! $setting->secret) {
                return null;
            }

            return Crypt::decryptString($setting->secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate recovery code and consume it.
     *
     * @gdpr-critical: validates recovery codes for authentication fallback
     */
    public function validateRecoveryCode(User $user, string $code): bool
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('user_two_factor_settings')) {
                return false;
            }
            $setting = UserTwoFactorSetting::where('user_id', $user->id)->first();
            if (! $setting || ! $setting->recovery_codes) {
                return false;
            }

            $codes = json_decode(Crypt::decryptString($setting->recovery_codes), true) ?? [];
            $key = array_search($code, $codes, true);

            if ($key === false) {
                return false;
            }

            // Remove used code
            unset($codes[$key]);
            $setting->update([
                'recovery_codes' => Crypt::encryptString(json_encode($codes)),
            ]);

            app(AuditLogger::class)->log(
                'recovery_code_used',
                User::class,
                $user->id,
                null,
                ['timestamp' => now()]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(User $user, int $count = 10): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('user_two_factor_settings')) {
                return [];
            }
            $codes = $this->generateRecoveryCodes($count);
            $setting = UserTwoFactorSetting::where('user_id', $user->id)->first();

            if ($setting) {
                $setting->update([
                    'recovery_codes' => Crypt::encryptString(json_encode($codes)),
                ]);
            }

            app(AuditLogger::class)->log(
                'recovery_codes_regenerated',
                User::class,
                $user->id,
                null,
                ['count' => $count]
            );

            return $codes;
        } catch (\Exception $e) {
            return [];
        }
    }
}

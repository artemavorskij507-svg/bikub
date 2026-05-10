<?php

namespace App\Filament\Pages\Security;

use App\Services\TwoFactorService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorSetup extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.security.two-factor-setup';

    protected static ?string $title = 'Two-Factor Authentication';

    protected static ?string $navigationLabel = '2FA Setup';

    protected static ?string $slug = 'security/two-factor-setup';

    public ?string $setup_state = 'checking';

    public ?string $temp_secret = null;

    public ?string $temp_qr_code = null;

    public ?array $temp_recovery_codes = [];

    public ?string $verification_code = '';

    protected ?TwoFactorService $twoFactorService = null;

    public function mount(): void
    {
        parent::mount();

        $this->twoFactorService = app(TwoFactorService::class);
        $user = Auth::user();

        if ($user && $this->twoFactorService) {
            $this->setup_state = $this->twoFactorService->isConfirmed($user) ? 'enabled' : 'setup';
        }
    }

    protected function getTwoFactorService(): TwoFactorService
    {
        if ($this->twoFactorService === null) {
            $this->twoFactorService = app(TwoFactorService::class);
        }

        return $this->twoFactorService;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Two-Factor Authentication')
                ->description('Protect your account with two-factor authentication')
                ->schema($this->setup_state === 'enabled' ? $this->getEnabledSchema() : $this->getSetupSchema())
                ->collapsible(false),
        ];
    }

    protected function getSetupSchema(): array
    {
        $content = 'Click "Generate QR Code" button below to get started.';

        if ($this->temp_qr_code) {
            $content = '<div style="text-align: center; margin: 20px 0;"><img src="'.htmlspecialchars($this->temp_qr_code).'" alt="QR Code" style="max-width: 300px; border: 1px solid #ddd; padding: 10px;"></div>';
        }

        return [
            Forms\Components\Placeholder::make('instructions')
                ->label('')
                ->content($content)
                ->visible(fn () => true),

            Forms\Components\TextInput::make('verification_code')
                ->label('6-Digit Code from Authenticator App')
                ->placeholder('000000')
                ->maxLength(6)
                ->inputMode('numeric')
                ->visible(fn () => (bool) $this->temp_secret),
        ];
    }

    protected function getEnabledSchema(): array
    {
        $codeContent = '';

        if ($this->temp_recovery_codes && count($this->temp_recovery_codes) > 0) {
            $codeContent = '<div style="background: #f3f4f6; padding: 12px; border-radius: 4px; font-family: monospace; margin: 10px 0;"><pre style="margin: 0;">'.implode("\n", $this->temp_recovery_codes).'</pre></div><p style="font-size: 12px; color: #666; margin: 10px 0 0 0;">Save these codes in a secure location. Each code can be used once if you lose access to your authenticator app.</p>';
        }

        return [
            Forms\Components\Placeholder::make('status')
                ->label('Status')
                ->content('✓ Two-Factor Authentication is Enabled'),

            Forms\Components\Placeholder::make('recovery_codes_info')
                ->label('Recovery Codes')
                ->content($codeContent)
                ->visible(fn () => (bool) count($this->temp_recovery_codes ?? [])),
        ];
    }

    protected function getActions(): array
    {
        return [
            // Setup actions (when 2FA not enabled)
            ...$this->setup_state !== 'enabled' ? [
                Actions\Action::make('generateSecret')
                    ->label('Generate QR Code')
                    ->action('generateSecret')
                    ->hidden(fn () => (bool) $this->temp_secret),

                Actions\Action::make('confirmTwoFactor')
                    ->label('Confirm & Enable 2FA')
                    ->action('confirmTwoFactor')
                    ->color('success')
                    ->hidden(fn () => ! ($this->temp_secret && $this->verification_code)),
            ] : [
                // Enabled actions
                Actions\Action::make('regenerateRecoveryCodes')
                    ->label('Regenerate Recovery Codes')
                    ->action('regenerateRecoveryCodes')
                    ->color('info'),

                Actions\Action::make('disableTwoFactor')
                    ->label('Disable 2FA')
                    ->action('startDisable2FA')
                    ->color('danger'),
            ],
        ];
    }

    public function generateSecret(): void
    {
        $user = Auth::user();

        if (! $user) {
            Notification::make()
                ->title('Error')
                ->body('User not authenticated')
                ->danger()
                ->send();

            return;
        }

        try {
            $result = $this->getTwoFactorService()->generateSecret($user);
            $this->temp_secret = $result['secret'];
            $this->temp_qr_code = $result['qr_code_url'];
            $this->verification_code = '';

            Notification::make()
                ->title('QR Code Generated')
                ->body('Scan the QR code with Google Authenticator or similar app')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function confirmTwoFactor(): void
    {
        $user = Auth::user();

        if (! $user) {
            Notification::make()
                ->title('Error')
                ->body('User not authenticated')
                ->danger()
                ->send();

            return;
        }

        // Rate limiting: 5 attempts per 60 seconds
        if (! RateLimiter::attempt('totp_verify:'.$user->id, 5, fn () => true, 60)) {
            Notification::make()
                ->title('Too Many Attempts')
                ->body('Please try again in a minute')
                ->danger()
                ->send();

            return;
        }

        if (! $this->temp_secret || ! $this->verification_code) {
            Notification::make()
                ->title('Error')
                ->body('Missing secret or code')
                ->danger()
                ->send();

            return;
        }

        if (! $this->getTwoFactorService()->verifyCode($this->temp_secret, $this->verification_code)) {
            Notification::make()
                ->title('Invalid Code')
                ->body('The code you entered is incorrect')
                ->danger()
                ->send();

            return;
        }

        try {
            $codes = $this->getTwoFactorService()->generateRecoveryCodes(10);
            $this->getTwoFactorService()->enable($user, $this->temp_secret, $codes);

            $this->temp_recovery_codes = $codes;
            $this->setup_state = 'enabled';
            $this->temp_secret = null;
            $this->temp_qr_code = null;
            $this->verification_code = '';

            Notification::make()
                ->title('2FA Enabled!')
                ->body('Your account is now protected with two-factor authentication')
                ->success()
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = Auth::user();

        if (! $user) {
            Notification::make()
                ->title('Error')
                ->body('User not authenticated')
                ->danger()
                ->send();

            return;
        }

        try {
            $codes = $this->getTwoFactorService()->regenerateRecoveryCodes($user);
            $this->temp_recovery_codes = $codes;

            Notification::make()
                ->title('Recovery Codes Regenerated')
                ->body('Save the new codes in a secure location')
                ->success()
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function startDisable2FA(): void
    {
        $this->setup_state = 'verify_disable';
    }

    public function disable2FA(?string $code = null): void
    {
        $user = Auth::user();

        if (! $user) {
            Notification::make()
                ->title('Error')
                ->body('User not authenticated')
                ->danger()
                ->send();

            return;
        }

        if (! $code) {
            Notification::make()
                ->title('Error')
                ->body('Code required')
                ->danger()
                ->send();

            return;
        }

        // Rate limiting: 5 attempts per 60 seconds
        if (! RateLimiter::attempt('totp_disable:'.$user->id, 5, fn () => true, 60)) {
            Notification::make()
                ->title('Too Many Attempts')
                ->body('Please try again in a minute')
                ->danger()
                ->send();

            return;
        }

        try {
            $secret = $this->getTwoFactorService()->getDecryptedSecret($user);

            if (! $secret || ! $this->getTwoFactorService()->verifyCode($secret, $code)) {
                Notification::make()
                    ->title('Invalid Code')
                    ->body('The code you entered is incorrect')
                    ->danger()
                    ->send();

                return;
            }

            $this->getTwoFactorService()->disable($user);
            $this->setup_state = 'setup';
            $this->temp_recovery_codes = [];

            Notification::make()
                ->title('2FA Disabled')
                ->body('Two-factor authentication has been disabled')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }
}

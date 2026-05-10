<?php

namespace App\Filament\Resources\PaymentSettingResource\Pages;

use App\Filament\Resources\PaymentSettingResource;
use App\Models\PaymentSetting;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentSettings extends ListRecords
{
    protected static string $resource = PaymentSettingResource::class;

    public function mount(): void
    {
        $this->seedStripeSettingsFromEnv();

        parent::mount();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function seedStripeSettingsFromEnv(): void
    {
        $publishable = trim((string) env('STRIPE_PUBLISHABLE_KEY', ''));
        $secret = trim((string) env('STRIPE_SECRET_KEY', ''));

        if ($publishable === '' || $secret === '') {
            return;
        }

        PaymentSetting::query()->updateOrCreate(
            ['gateway' => 'stripe', 'label' => 'Stripe Test'],
            [
                'publishable_key' => $publishable,
                'secret_key' => $secret,
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
                'currency' => env('STRIPE_CURRENCY', 'NOK'),
                'is_active' => true,
                'is_test_mode' => str_starts_with($publishable, 'pk_test_'),
                'additional_config' => [
                    'seeded_from_env' => true,
                    'updated_at' => now()->toDateTimeString(),
                ],
            ]
        );
    }
}

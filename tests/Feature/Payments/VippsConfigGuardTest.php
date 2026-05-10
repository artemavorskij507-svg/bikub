<?php

namespace Tests\Feature\Payments;

use Tests\TestCase;

class VippsConfigGuardTest extends TestCase
{
    public function test_vipps_init_returns_503_with_required_keys_when_not_configured(): void
    {
        config()->set('services.vipps.client_id', null);
        config()->set('services.vipps.client_secret', null);
        config()->set('services.vipps.subscription_key', null);
        config()->set('services.vipps.merchant_serial_number', null);

        $response = $this->postJson('/api/v1/payments/vipps/init', []);

        $response
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Vipps is not configured',
            ])
            ->assertJsonPath('required_config.0', 'VIPPS_CLIENT_ID');
    }

    public function test_vipps_auxiliary_callbacks_exist_and_return_success(): void
    {
        $shipping = $this->getJson('/api/v1/payments/vipps/shipping-details');
        $shipping->assertOk()->assertJsonPath('success', true);

        $consent = $this->postJson('/api/v1/payments/vipps/consent-removal', ['scope' => 'all']);
        $consent->assertOk()->assertJsonPath('success', true);
    }

    public function test_vipps_callback_validation_returns_json_422(): void
    {
        $response = $this->postJson('/api/v1/payments/vipps/callback', []);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['reference']);
    }
}

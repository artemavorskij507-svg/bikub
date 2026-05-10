<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_selector_available_for_guests(): void
    {
        $this->get('/auth/login')->assertOk();
    }

    public function test_admin_login_route_is_still_accessible(): void
    {
        $response = $this->get('/admin/login');

        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 302], true),
            'Expected admin login route to be reachable.'
        );
    }

    public function test_eid_callback_creates_user_and_logs_in(): void
    {
        config()->set('eid.providers.bankid', [
            'display_name' => 'BankID',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'redirect_uri' => 'https://example.com/auth/eid/bankid/callback',
            'issuer' => 'https://bankid.example',
            'authorization_endpoint' => 'https://bankid.example/authorize',
            'token_endpoint' => 'https://bankid.example/token',
            'userinfo_endpoint' => 'https://bankid.example/userinfo',
            'scope' => 'openid profile email',
        ]);

        Http::fake([
            'https://bankid.example/token' => Http::response([
                'access_token' => 'test-token',
            ], 200),
            'https://bankid.example/userinfo' => Http::response([
                'national_id' => '12345678901',
                'email' => 'bankid@example.com',
                'given_name' => 'Bank',
                'family_name' => 'ID',
            ], 200),
        ]);

        $this->withSession([
            'eid_login_provider' => 'bankid',
            'eid_login_state' => 'state-token',
        ])->get('/auth/eid/bankid/callback?code=auth-code&state=state-token')
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'eid_national_id' => '12345678901',
            'email' => 'bankid@example.com',
        ]);
    }
}

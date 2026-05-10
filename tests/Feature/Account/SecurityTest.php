<?php

namespace Tests\Feature\Account;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor_and_confirm_it(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/security/2fa/enable')
            ->assertOk();

        $secret = session('2fa_secret_pending');
        $this->assertNotNull($secret);

        $code = (new Google2FA)->getCurrentOtp($secret);

        $this->actingAs($user)
            ->withSession([
                '2fa_secret_pending' => $secret,
                '2fa_recovery_codes_pending' => ['code-one'],
                'two_factor_passed_at' => now(),
            ])
            ->post('/account/security/2fa/confirm', ['code' => $code])
            ->assertRedirect(route('account.security.index'));

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertTrue($user->hasTwoFactorEnabled());
    }

    public function test_user_cannot_confirm_two_factor_with_wrong_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/security/2fa/enable');

        $secret = session('2fa_secret_pending');

        $this->actingAs($user)
            ->withSession([
                '2fa_secret_pending' => $secret,
                '2fa_recovery_codes_pending' => ['code-one'],
            ])
            ->post('/account/security/2fa/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
    }

    public function test_eid_link_mode_binds_national_id_to_existing_user(): void
    {
        $user = User::factory()->create();

        config()->set('eid.providers.bankid', [
            'display_name' => 'BankID',
            'client_id' => 'client-id',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://example.com/auth/eid/bankid/callback',
            'issuer' => 'https://bankid.test',
            'authorization_endpoint' => 'https://bankid.test/auth',
            'token_endpoint' => 'https://bankid.test/token',
            'userinfo_endpoint' => 'https://bankid.test/userinfo',
            'scope' => 'openid profile email',
        ]);

        Http::fake([
            'https://bankid.test/token' => Http::response(['access_token' => 'token-123'], 200),
            'https://bankid.test/userinfo' => Http::response([
                'national_id' => '12345678901',
                'email' => 'bankid@example.com',
                'given_name' => 'Bank',
                'family_name' => 'ID',
            ], 200),
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/security/eid/link/bankid')
            ->assertRedirect();

        $this->get('/auth/eid/bankid')->assertStatus(302);

        $state = session('eid_login_state');

        $this->get("/account/security/eid/callback/bankid?code=test-code&state={$state}")
            ->assertRedirect(route('account.security.index'));

        $user->refresh();

        $this->assertEquals('12345678901', $user->eid_national_id);
        $this->assertEquals('bankid', $user->eid_provider);
    }

    public function test_eid_cannot_be_linked_if_national_id_already_used_by_other_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create([
            'eid_national_id' => '99988877766',
        ]);

        config()->set('eid.providers.bankid', [
            'display_name' => 'BankID',
            'client_id' => 'client-id',
            'client_secret' => 'secret',
            'redirect_uri' => 'https://example.com/auth/eid/bankid/callback',
            'issuer' => 'https://bankid.test',
            'authorization_endpoint' => 'https://bankid.test/auth',
            'token_endpoint' => 'https://bankid.test/token',
            'userinfo_endpoint' => 'https://bankid.test/userinfo',
            'scope' => 'openid profile email',
        ]);

        Http::fake([
            'https://bankid.test/token' => Http::response(['access_token' => 'token-123'], 200),
            'https://bankid.test/userinfo' => Http::response([
                'national_id' => '99988877766',
            ], 200),
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/security/eid/link/bankid');

        $this->get('/auth/eid/bankid')->assertStatus(302);

        $state = session('eid_login_state');

        $this->get("/account/security/eid/callback/bankid?code=test-code&state={$state}")
            ->assertStatus(409);

        $user->refresh();
        $this->assertNull($user->eid_national_id);
        $this->assertNotNull($other->fresh()->eid_national_id);
    }

    public function test_logout_other_sessions_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        UserSession::create([
            'user_id' => $user->id,
            'session_id' => 'other-session',
            'ip_address' => '1.1.1.1',
            'user_agent' => 'Test UA',
            'last_activity' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['two_factor_passed_at' => now()])
            ->post('/account/security/sessions/logout-others', [
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('account_user_sessions', [
            'session_id' => 'other-session',
            'user_id' => $user->id,
        ]);
    }
}

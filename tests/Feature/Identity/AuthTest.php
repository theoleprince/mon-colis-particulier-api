<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_username_returns_legacy_token_shape(): void
    {
        User::factory()->create(['username' => 'awa', 'password' => 'secret123']);

        $this->postJson('/api/login_check', ['username' => 'awa', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure([
                'token', 'tokenType', 'expiresAt',
                'data' => ['id', 'username', 'email', 'phone', 'profile', 'completion', 'preferences'],
            ])
            ->assertJsonPath('data.username', 'awa')
            ->assertJsonPath('data.profile', 'PARTICULIER');
    }

    public function test_login_with_local_phone_number(): void
    {
        User::factory()->create(['phone' => '+237677897012', 'password' => 'secret123']);

        $this->postJson('/api/login_check', ['username' => '677 89 70 12', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.phone', '+237677897012');
    }

    public function test_login_with_email_is_case_insensitive(): void
    {
        User::factory()->create(['email' => 'awa@moncolis.test', 'password' => 'secret123']);

        $this->postJson('/api/login_check', ['username' => 'AWA@moncolis.test', 'password' => 'secret123'])
            ->assertOk();
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['username' => 'awa', 'password' => 'secret123']);

        $this->postJson('/api/login_check', ['username' => 'awa', 'password' => 'nope'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_suspended_account_cannot_login(): void
    {
        User::factory()->suspended()->create(['username' => 'awa', 'password' => 'secret123']);

        $this->postJson('/api/login_check', ['username' => 'awa', 'password' => 'secret123'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_SUSPENDED');
    }

    public function test_register_creates_account_with_normalized_phone_and_preferences(): void
    {
        $this->postJson('/api/register', [
            'firstName' => 'Serge',
            'lastName' => 'Fotso',
            'username' => 'serge',
            'phone' => '699 45 12 30',
            'email' => 'Serge@Example.com',
            'password' => 'secret123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.phone', '+237699451230')
            ->assertJsonPath('data.email', 'serge@example.com')
            ->assertJsonPath('data.phoneVerified', false)
            ->assertJsonPath('data.preferences.language', 'fr')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['username' => 'serge', 'phone' => '+237699451230']);
    }

    public function test_register_rejects_already_used_phone_in_any_format(): void
    {
        User::factory()->create(['phone' => '+237699451230']);

        $this->postJson('/api/register', ['phone' => '00237699451230', 'password' => 'secret123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_register_requires_phone_or_email(): void
    {
        $this->postJson('/api/register', ['username' => 'serge', 'password' => 'secret123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'email']);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_client_app_code_is_enforced_when_configured(): void
    {
        config(['moncolis.client_app_codes' => ['MONCOLIS-PART']]);

        $this->getJson('/api/ping')->assertForbidden()->assertJsonPath('code', 'INVALID_CLIENT_APP');
        $this->getJson('/api/ping', ['client-app-code' => 'MONCOLIS-PART'])->assertOk();
    }

    public function test_protected_routes_answer_json_401_without_accept_header(): void
    {
        $this->get('/api/profile')->assertUnauthorized()->assertJsonStructure(['message']);
    }
}

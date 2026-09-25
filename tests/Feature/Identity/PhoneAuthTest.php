<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moncolis.otp.expose_in_response' => true]);
    }

    private function requestCode(string $phone = '699 45 12 30'): string
    {
        return $this->postJson('/api/auth/otp/request', ['phone' => $phone])
            ->assertStatus(202)
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.destination', '+237 •••• ••30')
            ->json('data.devCode');
    }

    public function test_unknown_number_signs_up_with_verified_phone_and_no_password(): void
    {
        $code = $this->requestCode();

        $this->postJson('/api/auth/otp/verify', ['phone' => '699451230', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('isNewUser', true)
            ->assertJsonPath('data.phone', '+237699451230')
            ->assertJsonPath('data.phoneVerified', true)
            ->assertJsonPath('data.hasPassword', false)
            ->assertJsonPath('data.preferences.language', 'fr')
            ->assertJsonStructure(['token', 'expiresAt']);

        $this->assertDatabaseHas('users', ['phone' => '+237699451230', 'password' => null]);
    }

    public function test_existing_number_signs_in(): void
    {
        $user = User::factory()->create(['phone' => '+237699451230', 'first_name' => 'Serge']);
        $code = $this->requestCode();

        $this->postJson('/api/auth/otp/verify', ['phone' => '+237 699 45 12 30', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('isNewUser', false)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.displayName', 'Serge');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_request_answer_does_not_reveal_whether_account_exists(): void
    {
        User::factory()->create(['phone' => '+237699451230']);

        $known = $this->postJson('/api/auth/otp/request', ['phone' => '699451230'])->json('data');
        $unknown = $this->postJson('/api/auth/otp/request', ['phone' => '699451231'])->json('data');

        unset($known['devCode'], $known['destination'], $unknown['devCode'], $unknown['destination']);
        $this->assertSame($known, $unknown);
    }

    public function test_wrong_code_is_rejected_and_nothing_is_created(): void
    {
        $code = $this->requestCode();

        $this->postJson('/api/auth/otp/verify', [
            'phone' => '699451230',
            'code' => $code === '000000' ? '111111' : '000000',
        ])->assertUnprocessable()->assertJsonPath('code', 'OTP_INVALID');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_suspended_account_cannot_sign_in_by_sms(): void
    {
        User::factory()->suspended()->create(['phone' => '+237699451230']);
        $code = $this->requestCode();

        $this->postJson('/api/auth/otp/verify', ['phone' => '699451230', 'code' => $code])
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_SUSPENDED');
    }

    public function test_passwordless_account_gets_a_clear_message_on_password_login(): void
    {
        User::factory()->create(['phone' => '+237699451230', 'password' => null]);

        $this->postJson('/api/login_check', ['username' => '699451230', 'password' => 'whatever'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'PASSWORD_NOT_SET');
    }

    public function test_new_user_completes_profile_then_sets_first_password(): void
    {
        $code = $this->requestCode();
        $token = $this->postJson('/api/auth/otp/verify', ['phone' => '699451230', 'code' => $code])
            ->json('token');

        $this->withToken($token)->patchJson('/api/profile', ['firstName' => 'Serge', 'lastName' => 'Fotso'])
            ->assertOk()
            ->assertJsonPath('data.fullName', 'Serge Fotso');

        // No current password to give: this is the first one.
        $this->withToken($token)->putJson('/api/profile/password', [
            'newPassword' => 'secret123',
            'newPasswordConfirmation' => 'secret123',
        ])->assertNoContent();

        $this->postJson('/api/login_check', ['username' => '699451230', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.hasPassword', true);
    }

    public function test_passwordless_account_can_be_deleted_with_confirmation(): void
    {
        $code = $this->requestCode();
        $token = $this->postJson('/api/auth/otp/verify', ['phone' => '699451230', 'code' => $code])
            ->json('token');

        $this->withToken($token)->deleteJson('/api/profile', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirm');

        $this->withToken($token)->deleteJson('/api/profile', ['confirm' => true])->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

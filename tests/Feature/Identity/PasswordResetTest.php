<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Modules\Identity\Mail\OtpCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moncolis.otp.expose_in_response' => true]);
        Mail::fake();
    }

    public function test_reset_by_sms_signs_in_and_revokes_other_sessions(): void
    {
        $user = User::factory()->create(['phone' => '+237677897012', 'password' => 'oldpass123']);
        $user->createToken('stolen-phone');

        $code = $this->postJson('/api/password/forgot', ['identifier' => '677 89 70 12'])
            ->assertStatus(202)
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.destination', '+237 •••• ••12')
            ->json('data.devCode');

        $this->postJson('/api/password/reset', [
            'identifier' => '677897012',
            'code' => $code,
            'password' => 'newpass456',
            'passwordConfirmation' => 'newpass456',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure(['token']);

        // Old session gone, only the new one remains.
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'stolen-phone']);

        $this->postJson('/api/login_check', ['username' => '677897012', 'password' => 'newpass456'])->assertOk();
        $this->postJson('/api/login_check', ['username' => '677897012', 'password' => 'oldpass123'])->assertUnauthorized();
    }

    public function test_reset_by_email_sends_the_code_by_mail_and_verifies_the_address(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'awa@moncolis.test']);

        $code = $this->postJson('/api/password/forgot', ['identifier' => 'AWA@moncolis.test'])
            ->assertStatus(202)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.destination', 'aw•••@moncolis.test')
            ->json('data.devCode');

        Mail::assertSent(OtpCodeMail::class, fn (OtpCodeMail $mail) => $mail->hasTo('awa@moncolis.test'));

        $this->postJson('/api/password/reset', [
            'identifier' => 'awa@moncolis.test',
            'code' => $code,
            'password' => 'newpass456',
            'passwordConfirmation' => 'newpass456',
        ])->assertOk()->assertJsonPath('data.emailVerified', true);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unknown_identifier_gets_the_same_answer_and_nothing_is_sent(): void
    {
        $this->postJson('/api/password/forgot', ['identifier' => 'nobody@moncolis.test'])
            ->assertStatus(202)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonMissingPath('data.devCode');

        Mail::assertNothingSent();

        $this->postJson('/api/password/reset', [
            'identifier' => 'nobody@moncolis.test',
            'code' => '123456',
            'password' => 'newpass456',
            'passwordConfirmation' => 'newpass456',
        ])->assertUnprocessable()->assertJsonPath('code', 'OTP_EXPIRED');
    }

    public function test_wrong_code_does_not_change_the_password(): void
    {
        User::factory()->create(['phone' => '+237677897012', 'password' => 'oldpass123']);
        $code = $this->postJson('/api/password/forgot', ['identifier' => '677897012'])->json('data.devCode');

        $this->postJson('/api/password/reset', [
            'identifier' => '677897012',
            'code' => $code === '000000' ? '111111' : '000000',
            'password' => 'newpass456',
            'passwordConfirmation' => 'newpass456',
        ])->assertUnprocessable()->assertJsonPath('code', 'OTP_INVALID');

        $this->postJson('/api/login_check', ['username' => '677897012', 'password' => 'oldpass123'])->assertOk();
    }

    public function test_validation(): void
    {
        $this->postJson('/api/password/forgot', ['identifier' => 'n/importe quoi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identifier');

        $this->postJson('/api/password/reset', [
            'identifier' => '677897012',
            'code' => '12',
            'password' => 'short',
            'passwordConfirmation' => 'other',
        ])->assertUnprocessable()->assertJsonValidationErrors(['code', 'password', 'passwordConfirmation']);
    }
}

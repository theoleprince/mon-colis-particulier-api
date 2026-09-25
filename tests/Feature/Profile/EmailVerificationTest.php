<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Modules\Identity\Mail\OtpCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moncolis.otp.expose_in_response' => true]);
        Mail::fake();
    }

    public function test_verify_email_with_code(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create(['email' => 'awa@moncolis.test']));

        $code = $this->postJson('/api/profile/email/otp')
            ->assertStatus(202)
            ->assertJsonPath('data.channel', 'email')
            ->json('data.devCode');

        Mail::assertSent(OtpCodeMail::class, fn (OtpCodeMail $mail) => $mail->hasTo('awa@moncolis.test'));

        $this->postJson('/api/profile/email/verify', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.emailVerified', true);
    }

    public function test_changing_email_invalidates_verification_and_can_be_verified_again(): void
    {
        $user = User::factory()->create(['email' => 'old@moncolis.test']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', ['email' => 'new@moncolis.test'])->assertJsonPath('data.emailVerified', false);
        $this->postJson('/api/profile/email/otp')->assertStatus(202);

        Mail::assertSent(OtpCodeMail::class, fn (OtpCodeMail $mail) => $mail->hasTo('new@moncolis.test'));
    }

    public function test_errors(): void
    {
        Sanctum::actingAs(User::factory()->create(['email' => null]));
        $this->postJson('/api/profile/email/otp')->assertUnprocessable()->assertJsonPath('code', 'EMAIL_MISSING');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/profile/email/otp')->assertUnprocessable()->assertJsonPath('code', 'EMAIL_ALREADY_VERIFIED');
    }
}

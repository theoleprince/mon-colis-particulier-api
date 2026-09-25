<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Modules\Identity\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhoneChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moncolis.otp.expose_in_response' => true]);
    }

    public function test_full_phone_change_flow(): void
    {
        $user = User::factory()->create(['phone' => '+237677897012']);
        Sanctum::actingAs($user);

        $code = $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])
            ->assertStatus(202)
            ->assertJsonPath('data.destination', '+237 •••• ••30')
            ->assertJsonPath('data.expiresIn', 300)
            ->json('data.devCode');

        $this->postJson('/api/profile/phone/verify', ['phone' => '699451230', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('data.phone', '+237699451230')
            ->assertJsonPath('data.phoneVerified', true);
    }

    public function test_wrong_code_is_rejected_then_locked_after_max_attempts(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $code = $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])->json('data.devCode');
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/profile/phone/verify', ['phone' => '699451230', 'code' => $wrong])
                ->assertJsonPath('code', 'OTP_INVALID');
        }

        $this->postJson('/api/profile/phone/verify', ['phone' => '699451230', 'code' => $code])
            ->assertStatus(429)
            ->assertJsonPath('code', 'OTP_TOO_MANY_ATTEMPTS');
    }

    public function test_expired_code_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $code = $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])->json('data.devCode');

        $this->travel(6)->minutes();

        $this->postJson('/api/profile/phone/verify', ['phone' => '699451230', 'code' => $code])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'OTP_EXPIRED');
    }

    public function test_resend_cooldown(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])->assertStatus(202);
        $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'OTP_COOLDOWN');

        $this->travel(61)->seconds();
        $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])->assertStatus(202);

        // Only the latest code stays usable.
        $this->assertSame(1, OtpCode::whereNull('consumed_at')->count());
    }

    public function test_number_already_used_by_another_account_is_refused(): void
    {
        User::factory()->create(['phone' => '+237699451230']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_code_is_stored_hashed_and_not_exposed_by_default(): void
    {
        config(['moncolis.otp.expose_in_response' => false]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/profile/phone/otp', ['phone' => '699451230'])
            ->assertStatus(202)
            ->assertJsonMissingPath('data.devCode');

        $this->assertMatchesRegularExpression('/^\$2y\$/', OtpCode::first()->code_hash);
    }
}

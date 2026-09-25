<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_profile_with_completion_and_preferences(): void
    {
        $user = User::factory()->create(['first_name' => 'Awa', 'avatar_path' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.displayName', 'Awa')
            ->assertJsonPath('data.completion.percent', 80)
            ->assertJsonPath('data.completion.missing', ['avatar'])
            ->assertJsonPath('data.preferences.notifyPush', true);
    }

    public function test_update_personal_information(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/profile', [
            'firstName' => 'Awa',
            'lastName' => 'Ngo Bikoe',
            'gender' => 'female',
            'birthDate' => '1995-04-12',
            'countryCode' => 'cm',
        ])
            ->assertOk()
            ->assertJsonPath('data.fullName', 'Awa Ngo Bikoe')
            ->assertJsonPath('data.gender', 'female')
            ->assertJsonPath('data.birthDate', '1995-04-12')
            ->assertJsonPath('data.countryCode', 'CM');
    }

    public function test_changing_email_resets_its_verification(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/profile', ['email' => 'new@moncolis.test'])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@moncolis.test')
            ->assertJsonPath('data.emailVerified', false);
    }

    public function test_phone_cannot_be_changed_without_otp(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/profile', ['phone' => '699451230'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@moncolis.test']);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/profile', ['email' => 'taken@moncolis.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_upload_and_remove_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
        ])->assertOk();

        $path = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($path);
        $this->assertNotNull($response->json('data.avatarUrl'));

        $this->deleteJson('/api/profile/avatar')
            ->assertOk()
            ->assertJsonPath('data.avatarUrl', null);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('avatar');
    }

    public function test_change_password_logs_out_other_devices(): void
    {
        $user = User::factory()->create(['password' => 'oldpass123']);
        $current = $user->createToken('this-phone')->plainTextToken;
        $user->createToken('other-phone');

        $this->withToken($current)->putJson('/api/profile/password', [
            'currentPassword' => 'oldpass123',
            'newPassword' => 'newpass456',
            'newPasswordConfirmation' => 'newpass456',
        ])->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'this-phone']);

        $this->postJson('/api/login_check', ['username' => $user->username, 'password' => 'newpass456'])
            ->assertOk();
    }

    public function test_change_password_requires_current_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['password' => 'oldpass123']));

        $this->putJson('/api/profile/password', [
            'currentPassword' => 'wrong',
            'newPassword' => 'newpass456',
            'newPasswordConfirmation' => 'newpass456',
        ])->assertUnprocessable()->assertJsonValidationErrors('currentPassword');
    }

    public function test_update_preferences(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/profile/preferences', [
            'language' => 'en',
            'theme' => 'green',
            'notifySms' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.theme', 'green')
            ->assertJsonPath('data.notifySms', false)
            ->assertJsonPath('data.notifyPush', true);

        $this->patchJson('/api/profile/preferences', ['language' => 'de'])
            ->assertUnprocessable();
    }

    public function test_delete_account_anonymizes_and_frees_phone(): void
    {
        $user = User::factory()->create(['phone' => '+237677897012', 'password' => 'secret123']);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/profile', ['password' => 'secret123'])
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $user->id, 'phone' => null, 'email' => null]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The number can be used for a new account.
        $this->postJson('/api/register', ['phone' => '677897012', 'password' => 'secret123'])
            ->assertCreated();
    }

    public function test_delete_account_requires_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['password' => 'secret123']));

        $this->deleteJson('/api/profile', ['password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}

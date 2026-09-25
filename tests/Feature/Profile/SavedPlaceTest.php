<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Modules\Profile\Models\SavedPlace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedPlaceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'home',
            'address' => 'Bastos, Yaoundé',
            'latitude' => 3.878,
            'longitude' => 11.517,
        ], $overrides);
    }

    public function test_create_home_with_default_label(): void
    {
        $this->postJson('/api/profile/places', $this->payload(['instructions' => 'Portail bleu']))
            ->assertCreated()
            ->assertJsonPath('data.type', 'home')
            ->assertJsonPath('data.label', 'Maison')
            ->assertJsonPath('data.instructions', 'Portail bleu')
            ->assertJsonPath('data.latitude', 3.878);
    }

    public function test_setting_home_again_replaces_it(): void
    {
        $this->postJson('/api/profile/places', $this->payload())->assertCreated();
        $this->postJson('/api/profile/places', $this->payload(['address' => 'Essos, Yaoundé']))
            ->assertOk()
            ->assertJsonPath('data.address', 'Essos, Yaoundé');

        $this->assertSame(1, $this->user->savedPlaces()->count());
    }

    public function test_list_orders_home_work_then_favourites(): void
    {
        SavedPlace::factory()->for($this->user)->create(['label' => 'Maman']);
        SavedPlace::factory()->for($this->user)->work()->create();
        SavedPlace::factory()->for($this->user)->home()->create();

        $this->getJson('/api/profile/places')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'home')
            ->assertJsonPath('data.1.type', 'work')
            ->assertJsonPath('data.2.label', 'Maman');
    }

    public function test_update_and_delete(): void
    {
        $place = SavedPlace::factory()->for($this->user)->create();

        $this->patchJson("/api/profile/places/{$place->id}", ['label' => 'Bureau Akwa'])
            ->assertOk()
            ->assertJsonPath('data.label', 'Bureau Akwa');

        $this->deleteJson("/api/profile/places/{$place->id}")->assertNoContent();
        $this->assertModelMissing($place);
    }

    public function test_cannot_turn_a_favourite_into_a_second_home(): void
    {
        SavedPlace::factory()->for($this->user)->home()->create();
        $favourite = SavedPlace::factory()->for($this->user)->create();

        $this->patchJson("/api/profile/places/{$favourite->id}", ['type' => 'home'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SAVED_PLACE_TYPE_TAKEN');
    }

    public function test_places_of_other_users_are_invisible(): void
    {
        $foreign = SavedPlace::factory()->create();

        $this->getJson("/api/profile/places/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/profile/places/{$foreign->id}", ['label' => 'x'])->assertNotFound();
        $this->deleteJson("/api/profile/places/{$foreign->id}")->assertNotFound();
    }

    public function test_validation(): void
    {
        $this->postJson('/api/profile/places', ['type' => 'castle', 'latitude' => 120])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'address', 'latitude', 'longitude']);
    }

    public function test_limit_of_saved_places(): void
    {
        config(['moncolis.profile.max_saved_places' => 2]);
        SavedPlace::factory()->for($this->user)->count(2)->create();

        $this->postJson('/api/profile/places', $this->payload(['type' => 'other']))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'SAVED_PLACES_LIMIT');
    }
}

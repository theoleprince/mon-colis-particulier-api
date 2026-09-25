<?php

namespace Tests\Feature\Delivery;

use App\Models\User;
use App\Modules\Delivery\Enums\DeliveryStatus;
use App\Modules\Delivery\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'first_name' => 'Awa', 'last_name' => 'Ngo Bikoe', 'phone' => '+237677897012',
        ]);
        Sanctum::actingAs($this->user);
    }

    /**
     * Quote id of the given vehicle for a 2 kg Bastos → Essos trip.
     */
    private function quote(string $vehicle = 'moto', array $extra = []): string
    {
        $quotes = $this->getJson('/api/tarifs/estimer?'.http_build_query([
            'pickupLatitude' => 3.8780, 'pickupLongitude' => 11.5170,
            'deliveryLatitude' => 3.8650, 'deliveryLongitude' => 11.5320,
            'weightKg' => 2,
        ] + $extra))->json('data.quotes');

        return collect($quotes)->firstWhere('vehicle.code', $vehicle)['quoteId'];
    }

    private function payload(string $quoteId, array $overrides = []): array
    {
        return array_replace_recursive([
            'quoteId' => $quoteId,
            'pickup' => [
                'address' => 'Bastos, Yaoundé',
                'details' => 'Immeuble bleu, 2e étage',
                'instructions' => 'Sonner au portail',
                'latitude' => 3.8780,
                'longitude' => 11.5170,
            ],
            'delivery' => ['address' => 'Essos, Yaoundé', 'latitude' => 3.8650, 'longitude' => 11.5320],
            'receiver' => ['name' => 'Serge Fotso', 'phone' => '699 45 12 30'],
            'packages' => [['natureCode' => 'DOCUMENT', 'description' => 'Dossier', 'weightKg' => 2]],
            'paymentMethod' => 'cash',
        ], $overrides);
    }

    public function test_create_cash_delivery_from_quote(): void
    {
        $quoteId = $this->quote();

        $response = $this->postJson('/api/livraisons', $this->payload($quoteId))
            ->assertCreated()
            ->assertJsonPath('data.status', 'RECHERCHE_COURSIER')
            ->assertJsonPath('data.statusLabel', 'Recherche d\'un coursier')
            ->assertJsonPath('data.vehicle.code', 'moto')
            ->assertJsonPath('data.vehicle.label', 'Moto')
            ->assertJsonPath('data.price.total', 1000)
            ->assertJsonPath('data.payment.status', 'cash_on_delivery')
            // Sender defaults to the connected user.
            ->assertJsonPath('data.sender.name', 'Awa Ngo Bikoe')
            ->assertJsonPath('data.sender.phone', '+237677897012')
            ->assertJsonPath('data.receiver.phone', '+237699451230')
            ->assertJsonPath('data.pickup.instructions', 'Sonner au portail')
            ->assertJsonPath('data.packages.0.nature.label', 'Documents')
            ->assertJsonPath('data.canCancel', true)
            ->assertJsonCount(1, 'data.events');

        $this->assertMatchesRegularExpression('/^MC-\d{6}-[2-9A-HJ-NP-Z]{4}$/', $response->json('data.reference'));
    }

    public function test_mobile_money_waits_for_payment_and_needs_a_number(): void
    {
        $this->postJson('/api/livraisons', $this->payload($this->quote(), ['paymentMethod' => 'mobile_money']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('paymentPhone');

        $this->postJson('/api/livraisons', $this->payload($this->quote(), [
            'paymentMethod' => 'mobile_money',
            'paymentPhone' => '677897012',
        ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'EN_ATTENTE_PAIEMENT')
            ->assertJsonPath('data.payment.status', 'pending')
            ->assertJsonPath('data.payment.phone', '+237677897012');
    }

    public function test_price_comes_from_the_quote_not_from_the_app(): void
    {
        $quoteId = $this->quote('tricycle', ['options' => 'express']);

        $this->postJson('/api/livraisons', $this->payload($quoteId, ['total' => 10, 'montant' => 10]))
            ->assertCreated()
            ->assertJsonPath('data.vehicle.code', 'tricycle')
            ->assertJsonPath('data.options', ['express'])
            ->assertJsonPath('data.price.total', 1950); // 1500 + 30 %
    }

    public function test_a_quote_is_used_only_once_and_expires(): void
    {
        $quoteId = $this->quote();
        $this->postJson('/api/livraisons', $this->payload($quoteId))->assertCreated();
        $this->postJson('/api/livraisons', $this->payload($quoteId))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'QUOTE_EXPIRED');

        $late = $this->quote();
        $this->travel(16)->minutes();
        $this->postJson('/api/livraisons', $this->payload($late))->assertJsonPath('code', 'QUOTE_EXPIRED');
    }

    public function test_quote_of_another_user_is_refused(): void
    {
        $quoteId = $this->quote();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/livraisons', $this->payload($quoteId))->assertJsonPath('code', 'QUOTE_EXPIRED');
    }

    public function test_delivery_must_match_the_quote(): void
    {
        $heavier = ['packages' => [['natureCode' => 'DOCUMENT', 'weightKg' => 1, 'quantity' => 3]]];
        $this->postJson('/api/livraisons', $this->payload($this->quote(), $heavier))
            ->assertJsonPath('code', 'QUOTE_MISMATCH');

        $elsewhere = ['delivery' => ['latitude' => 4.0511, 'longitude' => 9.7679]]; // Douala
        $this->postJson('/api/livraisons', $this->payload($this->quote(), $elsewhere))
            ->assertJsonPath('code', 'QUOTE_MISMATCH');

        // Pin nudged by a few metres: accepted.
        $nudged = ['delivery' => ['latitude' => 3.8652, 'longitude' => 11.5321]];
        $this->postJson('/api/livraisons', $this->payload($this->quote(), $nudged))->assertCreated();
    }

    public function test_validation(): void
    {
        $this->postJson('/api/livraisons', [
            'quoteId' => 'pas-un-uuid',
            'receiver' => ['name' => 'X', 'phone' => '12'],
            'packages' => [['natureCode' => 'ARMES', 'weightKg' => 0]],
            'paymentMethod' => 'bitcoin',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'quoteId', 'pickup.address', 'delivery.latitude', 'receiver.phone',
            'packages.0.natureCode', 'packages.0.weightKg', 'paymentMethod',
        ]);
    }

    public function test_history_filters_and_detail(): void
    {
        $first = $this->postJson('/api/livraisons', $this->payload($this->quote()))->json('data.reference');
        $second = $this->postJson('/api/livraisons', $this->payload($this->quote()))->json('data.reference');
        $this->postJson("/api/livraisons/{$first}/annuler", ['reason' => 'Erreur d\'adresse'])->assertOk();

        $this->getJson('/api/livraisons/mes-livraisons')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
        $this->getJson('/api/livraisons/mes-livraisons?status=active')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', $second);
        $this->getJson('/api/livraisons/mes-livraisons?status=cancelled')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.statusLabel', 'Annulée');

        $this->getJson("/api/livraisons/{$first}")
            ->assertOk()
            ->assertJsonPath('data.status', 'ANNULEE')
            ->assertJsonPath('data.cancelReason', 'Erreur d\'adresse')
            ->assertJsonPath('data.canCancel', false)
            ->assertJsonPath('data.events.1.label', 'Annulée');
    }

    public function test_other_customers_deliveries_are_invisible(): void
    {
        $reference = $this->postJson('/api/livraisons', $this->payload($this->quote()))->json('data.reference');
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/livraisons/{$reference}")->assertNotFound();
        $this->postJson("/api/livraisons/{$reference}/annuler")->assertNotFound();
        $this->getJson('/api/livraisons/mes-livraisons')->assertJsonCount(0, 'data');
    }

    public function test_cannot_cancel_after_pickup(): void
    {
        $reference = $this->postJson('/api/livraisons', $this->payload($this->quote()))->json('data.reference');
        Delivery::where('reference', $reference)->update(['status' => DeliveryStatus::PickedUp->value]);

        $this->postJson("/api/livraisons/{$reference}/annuler")
            ->assertStatus(409)
            ->assertJsonPath('code', 'CANCEL_NOT_ALLOWED');
    }

    public function test_parcel_photos_and_limit(): void
    {
        Storage::fake('public');
        $reference = $this->postJson('/api/livraisons', $this->payload($this->quote()))->json('data.reference');

        $this->post("/api/livraisons/{$reference}/medias", ['files' => [
            UploadedFile::fake()->image('colis1.jpg'),
            UploadedFile::fake()->image('colis2.jpg'),
        ]])->assertOk()->assertJsonCount(2, 'data.media')->assertJsonPath('data.media.0.type', 'image');

        Storage::disk('public')->assertExists(
            Delivery::where('reference', $reference)->first()->media()->first()->path
        );

        $this->post("/api/livraisons/{$reference}/medias", ['files' => array_map(
            fn ($i) => UploadedFile::fake()->image("c{$i}.jpg"),
            range(1, 5),
        )])->assertUnprocessable()->assertJsonPath('code', 'MEDIA_LIMIT');
    }

    public function test_legacy_create_path_still_works(): void
    {
        $this->postJson('/api/livraisons/create', $this->payload($this->quote()))->assertCreated();
    }
}

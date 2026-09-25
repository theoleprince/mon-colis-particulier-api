<?php

namespace Tests\Feature\Delivery;

use App\Models\User;
use App\Modules\Delivery\Models\DeliveryQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    private const TRIP = [
        'pickupLatitude' => 3.8780, 'pickupLongitude' => 11.5170,   // Bastos
        'deliveryLatitude' => 3.8650, 'deliveryLongitude' => 11.5320, // Essos
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    private function useOsrmDistance(int $meters): void
    {
        config(['moncolis.delivery.routing' => 'osrm']);
        Http::fake(['*/route/v1/driving/*' => Http::response(['routes' => [['distance' => $meters]]])]);
    }

    public function test_reference_data_is_public(): void
    {
        auth()->forgetGuards();

        $this->getJson('/api/configurations/nature-colis')
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.0.code', 'DOCUMENT');

        $this->getJson('/api/configurations/expedition')
            ->assertOk()
            ->assertJsonPath('data.currency', 'XAF')
            ->assertJsonCount(3, 'data.vehicles')
            ->assertJsonPath('data.vehicles.0.code', 'moto')
            ->assertJsonCount(5, 'data.options')
            ->assertJsonPath('data.paymentMethods.0.code', 'mobile_money');
    }

    public function test_price_follows_the_tariff_grid_on_the_road_distance(): void
    {
        $this->useOsrmDistance(8000);

        $response = $this->getJson('/api/tarifs/estimer?'.http_build_query(self::TRIP + ['weightKg' => 2]))
            ->assertOk()
            ->assertJsonPath('data.distanceKm', 8)
            ->assertJsonPath('data.routeSource', 'osrm')
            ->assertJsonPath('data.currency', 'XAF');

        // Moto: 500 + 150 × 8 = 1700 ; tricycle: 800 + 200 × 8 = 2400 ; camion: 3000 + 400 × 8 = 6200.
        $response->assertJsonPath('data.quotes.0.vehicle.code', 'moto')
            ->assertJsonPath('data.quotes.0.total', 1700)
            ->assertJsonPath('data.quotes.0.breakdown.0.label', 'Course Moto (8 km)')
            ->assertJsonPath('data.quotes.1.total', 2400)
            ->assertJsonPath('data.quotes.2.total', 6200)
            // Moto ETA: 6 min to reach the pickup + 8 km at 25 km/h (20 min).
            ->assertJsonPath('data.quotes.0.etaMinutes', 26);

        $this->assertDatabaseCount('delivery_quotes', 3);
    }

    public function test_options_and_rounding(): void
    {
        $this->useOsrmDistance(8000);

        $query = self::TRIP + ['weightKg' => 2, 'declaredValue' => 50000, 'options' => 'express,assurance,fragile'];
        $moto = $this->getJson('/api/tarifs/estimer?'.http_build_query($query))->json('data.quotes.0');

        // 1700 + express 30 % (510) + assurance 2 % of 50 000 (1000) + fragile 300 = 3510 → 3550.
        $this->assertSame(['course', 'express', 'fragile', 'assurance'], array_column($moto['breakdown'], 'code'));
        $this->assertSame(3550, $moto['total']);
    }

    public function test_extra_weight_and_vehicle_capacity(): void
    {
        $this->useOsrmDistance(8000);

        $quotes = $this->getJson('/api/tarifs/estimer?'.http_build_query(self::TRIP + ['weightKg' => 40]))
            ->json('data.quotes');

        $this->assertFalse($quotes[0]['available']);
        $this->assertSame('Poids maximum 30 kg', $quotes[0]['unavailableReason']);
        $this->assertNull($quotes[0]['quoteId']);
        // Tricycle: 2400 + 20 × (40 − 20) kg = 2800.
        $this->assertSame(2800, $quotes[1]['total']);
        $this->assertDatabaseCount('delivery_quotes', 2);
    }

    public function test_straight_line_fallback_when_osrm_fails(): void
    {
        config(['moncolis.delivery.routing' => 'osrm']);
        Http::fake(['*' => Http::response('down', 503)]);

        $this->getJson('/api/tarifs/estimer?'.http_build_query(self::TRIP + ['weightKg' => 2]))
            ->assertOk()
            ->assertJsonPath('data.routeSource', 'straight')
            // ≈ 2.2 km as the crow flies × 1.3; short trip: minimum fares apply.
            ->assertJsonPath('data.distanceKm', 2.87)
            ->assertJsonPath('data.quotes.0.total', 1000);
    }

    public function test_out_of_zone_and_validation(): void
    {
        $this->useOsrmDistance(80000);

        $this->getJson('/api/tarifs/estimer?'.http_build_query(self::TRIP + ['weightKg' => 2]))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'DISTANCE_TOO_LONG');

        $this->getJson('/api/tarifs/estimer?'.http_build_query(['weightKg' => 0, 'options' => 'teleportation']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pickupLatitude', 'deliveryLongitude', 'weightKg', 'options.0']);
    }

    public function test_quote_is_valid_fifteen_minutes(): void
    {
        $expiresAt = $this->getJson('/api/tarifs/estimer?'.http_build_query(self::TRIP + ['weightKg' => 2]))
            ->json('data.expiresAt');

        $this->assertEqualsWithDelta(now()->addMinutes(15)->timestamp, strtotime($expiresAt), 5);
        $this->assertTrue(DeliveryQuote::first()->isUsable());
    }
}

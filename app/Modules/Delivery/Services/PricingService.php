<?php

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Models\DeliveryOption;
use App\Modules\Delivery\Models\DeliveryQuote;
use App\Modules\Delivery\Models\VehicleType;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Carbon;

/**
 * Price computed by the server, one quote per vehicle:
 *   ride = max(min_fare, base_fare + per_km × km + per_extra_kg × kg above the included weight)
 *   + options (fixed, % of the ride or % of the declared value)
 *   total rounded up to the configured amount (50 FCFA).
 * Every available quote is stored: the delivery is later created from its id,
 * so the announced price is the charged price.
 */
class PricingService
{
    public function __construct(private readonly RouteDistanceService $routes)
    {
    }

    /**
     * @param  array{latitude: float, longitude: float}  $pickup
     * @param  array{latitude: float, longitude: float}  $dropoff
     * @param  list<string>  $optionCodes
     * @return array{distanceKm: float, routeSource: string, expiresAt: Carbon, quotes: list<array<string, mixed>>}
     */
    public function estimate(
        User $user,
        array $pickup,
        array $dropoff,
        float $weightKg,
        int $declaredValue,
        array $optionCodes,
    ): array {
        $route = $this->routes->between(
            $pickup['latitude'], $pickup['longitude'], $dropoff['latitude'], $dropoff['longitude'],
        );

        $maxKm = (float) config('moncolis.delivery.max_distance_km');
        if ($route['km'] > $maxKm) {
            throw new BusinessException(
                "Cette livraison dépasse la zone desservie ({$maxKm} km maximum).",
                'DISTANCE_TOO_LONG',
            );
        }

        $options = DeliveryOption::active()->whereIn('code', $optionCodes)->get();
        $expiresAt = now()->addMinutes((int) config('moncolis.delivery.quote_ttl_minutes'));
        $quotes = [];

        foreach (VehicleType::active()->get() as $vehicle) {
            $quotes[] = $this->quoteFor(
                $user, $vehicle, $route, $pickup, $dropoff, $weightKg, $declaredValue, $options, $expiresAt,
            );
        }

        return [
            'distanceKm' => $route['km'],
            'routeSource' => $route['source'],
            'expiresAt' => $expiresAt,
            'quotes' => $quotes,
        ];
    }

    /**
     * @param  array{km: float, source: string}  $route
     * @param  iterable<DeliveryOption>  $options
     * @return array<string, mixed>
     */
    private function quoteFor(
        User $user,
        VehicleType $vehicle,
        array $route,
        array $pickup,
        array $dropoff,
        float $weightKg,
        int $declaredValue,
        iterable $options,
        Carbon $expiresAt,
    ): array {
        $vehicleData = ['code' => $vehicle->code, 'label' => $vehicle->label, 'description' => $vehicle->description];

        if ($weightKg > $vehicle->max_weight_kg) {
            return [
                'quoteId' => null,
                'vehicle' => $vehicleData,
                'available' => false,
                'unavailableReason' => "Poids maximum {$this->number($vehicle->max_weight_kg)} kg",
            ];
        }

        $extraKg = max(0, $weightKg - $vehicle->included_weight_kg);
        $fare = (int) ceil(max(
            $vehicle->min_fare,
            $vehicle->base_fare + $vehicle->per_km * $route['km'] + $vehicle->per_extra_kg * $extraKg,
        ));

        $breakdown = [[
            'code' => 'course',
            'label' => "Course {$vehicle->label} ({$this->number($route['km'])} km)",
            'amount' => $fare,
        ]];
        foreach ($options as $option) {
            $breakdown[] = [
                'code' => $option->code,
                'label' => $option->label,
                'amount' => $option->priceFor($fare, $declaredValue),
            ];
        }

        $total = $this->roundUp(array_sum(array_column($breakdown, 'amount')));
        $etaMinutes = $vehicle->pickup_eta_minutes + (int) ceil($route['km'] / max(1, $vehicle->speed_kmh) * 60);

        $quote = DeliveryQuote::create([
            'user_id' => $user->id,
            'vehicle_code' => $vehicle->code,
            'pickup_latitude' => $pickup['latitude'],
            'pickup_longitude' => $pickup['longitude'],
            'delivery_latitude' => $dropoff['latitude'],
            'delivery_longitude' => $dropoff['longitude'],
            'distance_km' => $route['km'],
            'route_source' => $route['source'],
            'weight_kg' => $weightKg,
            'declared_value' => $declaredValue,
            'options' => collect($options)->pluck('code')->values()->all(),
            'breakdown' => $breakdown,
            'total' => $total,
            'eta_minutes' => $etaMinutes,
            'expires_at' => $expiresAt,
        ]);

        return [
            'quoteId' => $quote->id,
            'vehicle' => $vehicleData,
            'available' => true,
            'pickupEtaMinutes' => $vehicle->pickup_eta_minutes,
            'etaMinutes' => $etaMinutes,
            'breakdown' => $breakdown,
            'total' => $total,
        ];
    }

    private function roundUp(int $amount): int
    {
        $step = max(1, (int) config('moncolis.delivery.price_rounding'));

        return (int) (ceil($amount / $step) * $step);
    }

    /**
     * 4.2 → "4,2" ; 30.0 → "30".
     */
    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, ',', ' '), '0'), ',');
    }
}

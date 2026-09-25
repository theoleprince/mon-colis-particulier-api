<?php

namespace App\Modules\Delivery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Http\Requests\EstimateRequest;
use App\Modules\Delivery\Services\PricingService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Expédier — prix', 'Estimation du prix par véhicule.', weight: 11)]
class EstimateController extends Controller
{
    /**
     * Estimer le prix
     *
     * Calcule, pour chaque véhicule, le prix de la course (distance routière réelle, poids,
     * options). Chaque devis disponible a un `quoteId`, valable `expiresAt` (15 min) : la livraison
     * se crée avec ce `quoteId`, et le prix annoncé est celui qui est facturé.
     *
     * `weightKg` : poids total de l'envoi (somme poids × quantité). `options` : codes séparés
     * par des virgules (`express,assurance`).
     *
     * Erreur métier : `422 DISTANCE_TOO_LONG` (au-delà de la zone desservie).
     *
     * @response array{data: array{distanceKm: float, routeSource: 'osrm'|'straight', expiresAt: string, currency: string, quotes: list<array{quoteId: string|null, vehicle: array{code: string, label: string, description: string|null}, available: bool, unavailableReason?: string, pickupEtaMinutes?: int, etaMinutes?: int, breakdown?: list<array{code: string, label: string, amount: int}>, total?: int}>}}
     */
    public function __invoke(EstimateRequest $request, PricingService $pricing): JsonResponse
    {
        $result = $pricing->estimate(
            $request->user(),
            ['latitude' => (float) $request->validated('pickupLatitude'), 'longitude' => (float) $request->validated('pickupLongitude')],
            ['latitude' => (float) $request->validated('deliveryLatitude'), 'longitude' => (float) $request->validated('deliveryLongitude')],
            (float) $request->validated('weightKg'),
            (int) ($request->validated('declaredValue') ?? 0),
            $request->validated('options') ?? [],
        );

        return response()->json(['data' => [
            'distanceKm' => $result['distanceKm'],
            'routeSource' => $result['routeSource'],
            'expiresAt' => $result['expiresAt']->toIso8601String(),
            'currency' => config('moncolis.delivery.currency'),
            'quotes' => $result['quotes'],
        ]]);
    }
}

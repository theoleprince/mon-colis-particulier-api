<?php

namespace App\Modules\Delivery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Enums\PaymentMethod;
use App\Modules\Delivery\Http\Resources\DeliveryOptionResource;
use App\Modules\Delivery\Http\Resources\PackageNatureResource;
use App\Modules\Delivery\Http\Resources\VehicleTypeResource;
use App\Modules\Delivery\Models\DeliveryOption;
use App\Modules\Delivery\Models\PackageNature;
use App\Modules\Delivery\Models\VehicleType;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Expédier — référentiels', 'Natures de colis, véhicules, options et moyens de paiement.', weight: 10)]
class ConfigurationController extends Controller
{
    /**
     * Natures de colis
     *
     * Liste affichée à l'étape « Détails du colis ». Chemin identique à l'ancienne API.
     *
     * @unauthenticated
     */
    public function natures(): AnonymousResourceCollection
    {
        return PackageNatureResource::collection(PackageNature::active()->get());
    }

    /**
     * Configuration d'une expédition
     *
     * Véhicules (avec poids maximum et prix de départ), options payantes et moyens de paiement.
     *
     * @unauthenticated
     */
    public function expedition(): JsonResponse
    {
        return response()->json(['data' => [
            'currency' => config('moncolis.delivery.currency'),
            'vehicles' => VehicleTypeResource::collection(VehicleType::active()->get()),
            'options' => DeliveryOptionResource::collection(DeliveryOption::active()->get()),
            'paymentMethods' => array_map(
                fn (PaymentMethod $m) => ['code' => $m->value, 'label' => $m->label()],
                PaymentMethod::cases(),
            ),
            'quoteTtlMinutes' => (int) config('moncolis.delivery.quote_ttl_minutes'),
            'maxDistanceKm' => (float) config('moncolis.delivery.max_distance_km'),
        ]]);
    }
}

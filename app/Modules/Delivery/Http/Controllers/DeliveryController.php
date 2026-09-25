<?php

namespace App\Modules\Delivery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Enums\DeliveryStatus;
use App\Modules\Delivery\Http\Requests\CancelDeliveryRequest;
use App\Modules\Delivery\Http\Requests\StoreDeliveryRequest;
use App\Modules\Delivery\Http\Requests\UploadDeliveryMediaRequest;
use App\Modules\Delivery\Http\Resources\DeliveryResource;
use App\Modules\Delivery\Http\Resources\DeliverySummaryResource;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Services\DeliveryService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Expédier — livraisons', 'Création, suivi, historique et annulation des livraisons.', weight: 12)]
class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries)
    {
    }

    /**
     * Créer une livraison
     *
     * À partir d'un devis (`quoteId` de « Estimer le prix ») : le prix et le véhicule viennent du
     * devis, jamais de l'app. Les points, le poids total et la valeur déclarée doivent correspondre
     * au devis. L'expéditeur est par défaut l'utilisateur connecté.
     *
     * Statut initial : `EN_ATTENTE_PAIEMENT` (Mobile Money) ou `RECHERCHE_COURSIER` (espèces).
     *
     * Erreurs métier : `422 QUOTE_EXPIRED` (devis expiré, déjà utilisé ou inconnu),
     * `422 QUOTE_MISMATCH` (la livraison a changé depuis l'estimation).
     */
    public function store(StoreDeliveryRequest $request): DeliveryResource
    {
        $delivery = $this->deliveries->create($request->user(), $request->deliveryData());

        return DeliveryResource::make($this->loadDetail($delivery));
    }

    /**
     * Mes livraisons
     *
     * Historique paginé, les plus récentes d'abord.
     */
    #[QueryParameter('status', description: 'active (en cours, par défaut) · done (livrées) · cancelled (annulées) · all', type: 'string', default: 'all')]
    public function index(Request $request): AnonymousResourceCollection
    {
        $group = (string) $request->query('status', 'all');

        $deliveries = Delivery::query()
            ->where('user_id', $request->user()->id)
            ->when($group !== 'all', fn ($q) => $q->whereIn('status', DeliveryStatus::group($group)))
            ->latest()
            ->latest('id')
            ->paginate(20);

        return DeliverySummaryResource::collection($deliveries);
    }

    /**
     * Détail d'une livraison
     *
     * Adresses, colis, prix, paiement, photos et chronologie des statuts.
     */
    public function show(Request $request, string $reference): DeliveryResource
    {
        return DeliveryResource::make($this->loadDetail($this->findOwned($request, $reference)));
    }

    /**
     * Ajouter des photos / vidéos du colis
     *
     * Multipart, champ `files[]` : jpg, png, webp, mp4 ou mov (50 Mo max par fichier).
     * 6 photos et 1 vidéo maximum par livraison.
     *
     * Erreurs métier : `422 MEDIA_LIMIT`, `409 DELIVERY_FINISHED`.
     */
    public function uploadMedia(UploadDeliveryMediaRequest $request, string $reference): DeliveryResource
    {
        $delivery = $this->findOwned($request, $reference);
        $this->deliveries->addMedia($delivery, $request->file('files'));

        return DeliveryResource::make($this->loadDetail($delivery));
    }

    /**
     * Annuler une livraison
     *
     * Possible jusqu'au ramassage du colis.
     *
     * Erreur métier : `409 CANCEL_NOT_ALLOWED`.
     */
    public function cancel(CancelDeliveryRequest $request, string $reference): DeliveryResource
    {
        $delivery = $this->deliveries->cancel($this->findOwned($request, $reference), $request->validated('reason'));

        return DeliveryResource::make($this->loadDetail($delivery));
    }

    /**
     * Another customer's delivery answers 404 (its existence is not revealed).
     */
    private function findOwned(Request $request, string $reference): Delivery
    {
        return Delivery::query()
            ->where('reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    private function loadDetail(Delivery $delivery): Delivery
    {
        return $delivery->load(['vehicle', 'packages.nature', 'media', 'events']);
    }
}

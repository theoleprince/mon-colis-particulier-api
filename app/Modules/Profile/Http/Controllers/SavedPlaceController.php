<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\SavedPlaceRequest;
use App\Modules\Profile\Http\Resources\SavedPlaceResource;
use App\Modules\Profile\Models\SavedPlace;
use App\Modules\Profile\Services\SavedPlaceService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Mes adresses', 'Maison, Travail et adresses favorites, réutilisées comme points de départ / d\'arrivée.', weight: 4)]
class SavedPlaceController extends Controller
{
    public function __construct(private readonly SavedPlaceService $places)
    {
    }

    /**
     * Lister mes adresses
     *
     * Ordre : Maison, Travail, puis favoris (les plus récemment utilisés d'abord).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return SavedPlaceResource::collection($this->places->list($request->user()));
    }

    /**
     * Ajouter une adresse
     *
     * Un seul `home` et un seul `work` : les renvoyer remplace l'adresse existante (réponse 200 au lieu de 201).
     * `label` par défaut : « Maison », « Travail » ou « Adresse favorite ».
     * Erreur métier : `422 SAVED_PLACES_LIMIT` (20 adresses max).
     */
    public function store(SavedPlaceRequest $request): JsonResponse
    {
        $result = $this->places->save($request->user(), $request->placeAttributes());

        /** @status 201 */
        return SavedPlaceResource::make($result['place'])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    /**
     * Détail d'une adresse
     */
    public function show(Request $request, int $place): SavedPlaceResource
    {
        return SavedPlaceResource::make($this->findOwned($request, $place));
    }

    /**
     * Modifier une adresse
     *
     * Erreur métier : `409 SAVED_PLACE_TYPE_TAKEN` si l'on transforme une adresse en `home` / `work` déjà existante.
     */
    public function update(SavedPlaceRequest $request, int $place): SavedPlaceResource
    {
        return SavedPlaceResource::make(
            $this->places->update($this->findOwned($request, $place), $request->placeAttributes())
        );
    }

    /**
     * Supprimer une adresse
     */
    public function destroy(Request $request, int $place): Response
    {
        $this->places->delete($this->findOwned($request, $place));

        return response()->noContent();
    }

    /**
     * Another user's place answers 404, never 403 (does not leak its existence).
     */
    private function findOwned(Request $request, int $id): SavedPlace
    {
        return $request->user()->savedPlaces()->findOrFail($id);
    }
}

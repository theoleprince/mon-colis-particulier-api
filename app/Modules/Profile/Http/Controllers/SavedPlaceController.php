<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\SavedPlaceRequest;
use App\Modules\Profile\Http\Resources\SavedPlaceResource;
use App\Modules\Profile\Models\SavedPlace;
use App\Modules\Profile\Services\SavedPlaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SavedPlaceController extends Controller
{
    public function __construct(private readonly SavedPlaceService $places)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return SavedPlaceResource::collection($this->places->list($request->user()));
    }

    public function store(SavedPlaceRequest $request): JsonResponse
    {
        $result = $this->places->save($request->user(), $request->placeAttributes());

        return SavedPlaceResource::make($result['place'])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    public function show(Request $request, int $place): SavedPlaceResource
    {
        return SavedPlaceResource::make($this->findOwned($request, $place));
    }

    public function update(SavedPlaceRequest $request, int $place): SavedPlaceResource
    {
        return SavedPlaceResource::make(
            $this->places->update($this->findOwned($request, $place), $request->placeAttributes())
        );
    }

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

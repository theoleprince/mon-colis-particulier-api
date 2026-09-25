<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\DeleteAccountRequest;
use App\Modules\Profile\Http\Requests\UpdateAvatarRequest;
use App\Modules\Profile\Http\Requests\UpdateProfileRequest;
use App\Modules\Profile\Http\Resources\ProfileResource;
use App\Modules\Profile\Services\AccountSecurityService;
use App\Modules\Profile\Services\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Profil', 'Informations personnelles, photo et suppression du compte.', weight: 1)]
class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles)
    {
    }

    /**
     * Mon profil
     *
     * Inclut `completion` (pourcentage + champs manquants parmi `firstName`, `lastName`,
     * `phoneVerified`, `email`, `avatar`) pour le bandeau « Complétez votre profil ».
     */
    public function show(Request $request): ProfileResource
    {
        return ProfileResource::make($this->profiles->forDisplay($request->user()));
    }

    /**
     * Modifier mon profil
     *
     * Mise à jour partielle : seuls les champs envoyés sont modifiés. Changer l'e-mail remet
     * `emailVerified` à `false`. Le numéro ne se change pas ici (voir « Sécurité du compte »).
     */
    public function update(UpdateProfileRequest $request): ProfileResource
    {
        return ProfileResource::make(
            $this->profiles->update($request->user(), $request->profileAttributes())
        );
    }

    /**
     * Changer ma photo
     *
     * Multipart, champ `avatar` : jpg / png / webp, 5 Mo max, 100×100 px minimum.
     */
    public function updateAvatar(UpdateAvatarRequest $request): ProfileResource
    {
        return ProfileResource::make(
            $this->profiles->updateAvatar($request->user(), $request->file('avatar'))
        );
    }

    /**
     * Supprimer ma photo
     */
    public function destroyAvatar(Request $request): ProfileResource
    {
        return ProfileResource::make($this->profiles->removeAvatar($request->user()));
    }

    /**
     * Supprimer mon compte
     *
     * Efface les données personnelles, révoque tous les jetons et libère le numéro / l'e-mail.
     * `password` est demandé si le compte en a un ; sinon (compte créé par code SMS), `confirm: true`.
     * L'historique des livraisons est conservé de façon anonyme.
     */
    public function destroy(DeleteAccountRequest $request, AccountSecurityService $security): Response
    {
        $security->deleteAccount($request->user(), $request->validated('reason'));

        return response()->noContent();
    }
}

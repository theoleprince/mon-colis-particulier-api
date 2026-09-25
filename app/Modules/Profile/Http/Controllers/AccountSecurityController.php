<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\ConfirmPhoneChangeRequest;
use App\Modules\Profile\Http\Requests\RequestPhoneChangeRequest;
use App\Modules\Profile\Http\Requests\UpdatePasswordRequest;
use App\Modules\Profile\Http\Resources\ProfileResource;
use App\Modules\Profile\Services\AccountSecurityService;
use App\Modules\Profile\Services\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

#[Group('Sécurité du compte', 'Mot de passe et changement de numéro par code SMS.', weight: 2)]
class AccountSecurityController extends Controller
{
    public function __construct(private readonly AccountSecurityService $security)
    {
    }

    /**
     * Changer mon mot de passe
     *
     * Par défaut (`logoutOtherDevices`), les autres appareils sont déconnectés ; le jeton courant reste valide.
     */
    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $this->security->changePassword(
            $request->user(),
            $request->validated('newPassword'),
            $request->boolean('logoutOtherDevices', true),
        );

        return response()->noContent();
    }

    /**
     * Changer de numéro — 1. envoyer le code
     *
     * Envoie un code à 6 chiffres par SMS au nouveau numéro. `devCode` n'est renvoyé qu'en environnement local.
     *
     * Erreurs métier : `429 OTP_COOLDOWN` (nouvel envoi avant `resendIn` secondes), `422 SAME_PHONE`.
     */
    public function requestPhoneChange(RequestPhoneChangeRequest $request): JsonResponse
    {
        $challenge = $this->security->requestPhoneChange($request->user(), $request->normalizedPhone());

        /**
         * @status 202
         *
         * @body array{data: array{destination: string, expiresIn: int, resendIn: int, devCode?: string}}
         */
        return response()->json(['data' => $challenge->toArray()], 202);
    }

    /**
     * Changer de numéro — 2. valider le code
     *
     * Erreurs métier : `422 OTP_INVALID`, `422 OTP_EXPIRED`, `429 OTP_TOO_MANY_ATTEMPTS` (5 essais).
     */
    public function confirmPhoneChange(ConfirmPhoneChangeRequest $request, ProfileService $profiles): ProfileResource
    {
        $user = $this->security->confirmPhoneChange(
            $request->user(),
            $request->normalizedPhone(),
            $request->validated('code'),
        );

        return ProfileResource::make($profiles->forDisplay($user));
    }
}

<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Controllers\Concerns\RespondsWithToken;
use App\Modules\Identity\Http\Requests\ForgotPasswordRequest;
use App\Modules\Identity\Http\Requests\ResetPasswordRequest;
use App\Modules\Identity\Services\PasswordResetService;
use App\Modules\Profile\Http\Resources\ProfileResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mot de passe oublié', 'Réinitialisation par code envoyé par SMS ou par e-mail.', weight: 0)]
class PasswordResetController extends Controller
{
    use RespondsWithToken;

    public function __construct(private readonly PasswordResetService $resets)
    {
    }

    /**
     * Recevoir un code de réinitialisation
     *
     * `identifier` : numéro de téléphone (code par SMS) ou adresse e-mail (code par e-mail).
     * La réponse est identique que le compte existe ou non (aucune information divulguée).
     *
     * Erreur métier : `429 OTP_COOLDOWN`.
     *
     * @unauthenticated
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $challenge = $this->resets->requestCode($request->validated('identifier'));

        /**
         * @status 202
         *
         * @body array{data: array{channel: 'sms'|'email', destination: string, expiresIn: int, resendIn: int, devCode?: string}}
         */
        return response()->json(['data' => $challenge->toArray()], 202);
    }

    /**
     * Choisir un nouveau mot de passe
     *
     * Vérifie le code, enregistre le nouveau mot de passe, déconnecte tous les autres appareils
     * et connecte directement l'utilisateur.
     *
     * Erreurs métier : `422 OTP_INVALID`, `422 OTP_EXPIRED`, `429 OTP_TOO_MANY_ATTEMPTS`.
     *
     * @unauthenticated
     *
     * @response array{token: string, tokenType: 'Bearer', expiresAt: string, data: ProfileResource}
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->resets->reset(
            $request->validated('identifier'),
            $request->validated('code'),
            $request->validated('password'),
            $request->validated('deviceName'),
        );

        return $this->tokenResponse($request, $result['user'], $result['token'], $result['expiresAt']);
    }
}

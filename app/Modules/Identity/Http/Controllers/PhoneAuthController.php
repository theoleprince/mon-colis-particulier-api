<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Controllers\Concerns\RespondsWithToken;
use App\Modules\Identity\Http\Requests\PhoneCodeRequest;
use App\Modules\Identity\Http\Requests\VerifyPhoneCodeRequest;
use App\Modules\Identity\Services\PhoneAuthService;
use App\Modules\Profile\Http\Resources\ProfileResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Connexion par SMS', 'Connexion et inscription façon Yango : numéro → code SMS → connecté.', weight: -1)]
class PhoneAuthController extends Controller
{
    use RespondsWithToken;

    public function __construct(private readonly PhoneAuthService $phoneAuth)
    {
    }

    /**
     * Recevoir un code de connexion
     *
     * Envoie un code à 6 chiffres par SMS. Même réponse que le numéro ait déjà un compte ou non.
     * `devCode` n'est renvoyé qu'en environnement local.
     *
     * Erreur métier : `429 OTP_COOLDOWN` (nouvel envoi avant `resendIn` secondes).
     *
     * @unauthenticated
     */
    public function requestCode(PhoneCodeRequest $request): JsonResponse
    {
        $challenge = $this->phoneAuth->requestCode($request->normalizedPhone());

        /**
         * @status 202
         *
         * @body array{data: array{channel: 'sms', destination: string, expiresIn: int, resendIn: int, devCode?: string}}
         */
        return response()->json(['data' => $challenge->toArray()], 202);
    }

    /**
     * Se connecter avec le code
     *
     * Vérifie le code puis connecte l'utilisateur. Si le numéro n'avait pas de compte, il est créé
     * (`isNewUser: true`) : l'app demande alors le prénom et le nom (`PATCH /profile`).
     *
     * Erreurs métier : `422 OTP_INVALID`, `422 OTP_EXPIRED`, `429 OTP_TOO_MANY_ATTEMPTS`, `403 ACCOUNT_SUSPENDED`.
     *
     * @unauthenticated
     *
     * @response array{token: string, tokenType: 'Bearer', expiresAt: string, isNewUser: bool, data: ProfileResource}
     */
    public function verifyCode(VerifyPhoneCodeRequest $request): JsonResponse
    {
        $result = $this->phoneAuth->verifyCode(
            $request->normalizedPhone(),
            $request->validated('code'),
            $request->validated('deviceName'),
        );

        return $this->tokenResponse(
            $request,
            $result['user'],
            $result['token'],
            $result['expiresAt'],
            extra: ['isNewUser' => $result['isNewUser']],
        );
    }
}

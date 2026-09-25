<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Controllers\Concerns\RespondsWithToken;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Services\AuthService;
use App\Modules\Profile\Http\Resources\ProfileResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

#[Group('Authentification', 'Connexion par mot de passe, inscription classique et déconnexion.', weight: 0)]
class AuthController extends Controller
{
    use RespondsWithToken;

    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * Connexion
     *
     * `username` accepte un nom d'utilisateur, un e-mail ou un numéro de téléphone
     * (`677897012`, `+237 677 89 70 12`, `00237677897012`...).
     *
     * Erreurs métier : `401 INVALID_CREDENTIALS`, `403 ACCOUNT_SUSPENDED`. Limité à 5 essais / minute.
     *
     * @unauthenticated
     *
     * @response array{token: string, tokenType: 'Bearer', expiresAt: string, data: ProfileResource}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            (string) $request->validated('username'),
            (string) $request->validated('password'),
            $request->input('deviceName'),
        );

        return $this->tokenResponse($request, $result['user'], $result['token'], $result['expiresAt']);
    }

    /**
     * Inscription
     *
     * `phone` ou `email` obligatoire. Le numéro est normalisé au format international (+237...).
     * Réponse identique à la connexion (le compte est connecté directement).
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->userAttributes(), $request->input('deviceName'));

        /**
         * @status 201
         *
         * @body array{token: string, tokenType: 'Bearer', expiresAt: string, data: ProfileResource}
         */
        return $this->tokenResponse($request, $result['user'], $result['token'], $result['expiresAt'], 201);
    }

    /**
     * Déconnexion
     *
     * Révoque le jeton utilisé pour cet appel (les autres appareils restent connectés).
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }
}

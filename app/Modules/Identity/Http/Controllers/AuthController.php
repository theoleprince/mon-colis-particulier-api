<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Services\AuthService;
use App\Modules\Profile\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            (string) $request->validated('username'),
            (string) $request->validated('password'),
            $request->input('deviceName'),
        );

        return $this->tokenResponse($request, $result['user'], $result['token'], $result['expiresAt']);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->userAttributes(), $request->input('deviceName'));

        return $this->tokenResponse($request, $result['user'], $result['token'], $result['expiresAt'], 201);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->user());

        return response()->noContent();
    }

    /**
     * Legacy shape expected by the Flutter UserModel: `{ token, data: {...} }`.
     */
    private function tokenResponse(Request $request, User $user, string $token, Carbon $expiresAt, int $status = 200): JsonResponse
    {
        $user->load('preferences');

        return response()->json([
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresAt' => $expiresAt->toIso8601String(),
            'data' => ProfileResource::make($user)->resolve($request),
        ], $status);
    }
}

<?php

namespace App\Modules\Identity\Http\Controllers\Concerns;

use App\Models\User;
use App\Modules\Profile\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait RespondsWithToken
{
    /**
     * Legacy shape expected by the Flutter UserModel: `{ token, data: {...} }`.
     *
     * @param  array<string, mixed>  $extra  additional top-level keys (e.g. isNewUser)
     */
    protected function tokenResponse(
        Request $request,
        User $user,
        string $token,
        Carbon $expiresAt,
        int $status = 200,
        array $extra = [],
    ): JsonResponse {
        $user->load('preferences');

        return response()->json([
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresAt' => $expiresAt->toIso8601String(),
            ...$extra,
            'data' => ProfileResource::make($user)->resolve($request),
        ], $status);
    }
}

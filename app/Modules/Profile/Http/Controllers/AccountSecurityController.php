<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\ConfirmPhoneChangeRequest;
use App\Modules\Profile\Http\Requests\RequestPhoneChangeRequest;
use App\Modules\Profile\Http\Requests\UpdatePasswordRequest;
use App\Modules\Profile\Http\Resources\ProfileResource;
use App\Modules\Profile\Services\AccountSecurityService;
use App\Modules\Profile\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AccountSecurityController extends Controller
{
    public function __construct(private readonly AccountSecurityService $security)
    {
    }

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
     * Step 1: sends a code by SMS to the new number.
     */
    public function requestPhoneChange(RequestPhoneChangeRequest $request): JsonResponse
    {
        $challenge = $this->security->requestPhoneChange($request->user(), $request->normalizedPhone());

        return response()->json(['data' => $challenge->toArray()], 202);
    }

    /**
     * Step 2: checks the code and switches the number.
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

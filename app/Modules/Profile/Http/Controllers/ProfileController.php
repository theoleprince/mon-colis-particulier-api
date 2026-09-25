<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\DeleteAccountRequest;
use App\Modules\Profile\Http\Requests\UpdateAvatarRequest;
use App\Modules\Profile\Http\Requests\UpdateProfileRequest;
use App\Modules\Profile\Http\Resources\ProfileResource;
use App\Modules\Profile\Services\AccountSecurityService;
use App\Modules\Profile\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles)
    {
    }

    public function show(Request $request): ProfileResource
    {
        return ProfileResource::make($this->profiles->forDisplay($request->user()));
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        return ProfileResource::make(
            $this->profiles->update($request->user(), $request->profileAttributes())
        );
    }

    public function updateAvatar(UpdateAvatarRequest $request): ProfileResource
    {
        return ProfileResource::make(
            $this->profiles->updateAvatar($request->user(), $request->file('avatar'))
        );
    }

    public function destroyAvatar(Request $request): ProfileResource
    {
        return ProfileResource::make($this->profiles->removeAvatar($request->user()));
    }

    public function destroy(DeleteAccountRequest $request, AccountSecurityService $security): Response
    {
        $security->deleteAccount($request->user(), $request->validated('reason'));

        return response()->noContent();
    }
}

<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\UpdatePreferencesRequest;
use App\Modules\Profile\Http\Resources\PreferencesResource;
use App\Modules\Profile\Services\ProfileService;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function __construct(private readonly ProfileService $profiles)
    {
    }

    public function show(Request $request): PreferencesResource
    {
        return PreferencesResource::make($this->profiles->preferencesOf($request->user()));
    }

    public function update(UpdatePreferencesRequest $request): PreferencesResource
    {
        return PreferencesResource::make(
            $this->profiles->updatePreferences($request->user(), $request->preferenceAttributes())
        );
    }
}

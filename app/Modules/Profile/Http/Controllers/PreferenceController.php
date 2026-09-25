<?php

namespace App\Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Http\Requests\UpdatePreferencesRequest;
use App\Modules\Profile\Http\Resources\PreferencesResource;
use App\Modules\Profile\Services\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Préférences', 'Langue, thème et notifications.', weight: 3)]
class PreferenceController extends Controller
{
    public function __construct(private readonly ProfileService $profiles)
    {
    }

    /**
     * Mes préférences
     */
    public function show(Request $request): PreferencesResource
    {
        return PreferencesResource::make($this->profiles->preferencesOf($request->user()));
    }

    /**
     * Modifier mes préférences
     *
     * Mise à jour partielle. `language` : `fr` ou `en`.
     */
    public function update(UpdatePreferencesRequest $request): PreferencesResource
    {
        return PreferencesResource::make(
            $this->profiles->updatePreferences($request->user(), $request->preferenceAttributes())
        );
    }
}

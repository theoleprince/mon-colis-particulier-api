<?php

namespace App\Modules\Profile\Http\Resources;

use App\Models\User;
use App\Modules\Profile\Support\ProfileCompletion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'fullName' => $this->fullName(),
            'displayName' => $this->displayName(),
            'phone' => $this->phone,
            'phoneVerified' => $this->phone_verified_at !== null,
            'email' => $this->email,
            'emailVerified' => $this->email_verified_at !== null,
            'avatarUrl' => $this->avatar_path
                ? Storage::disk(config('moncolis.profile.avatar_disk'))->url($this->avatar_path)
                : null,
            'gender' => $this->gender?->value,
            'birthDate' => $this->birth_date?->toDateString(),
            'countryCode' => $this->country_code,
            // Legacy field read by the Flutter UserModel.
            'profile' => 'PARTICULIER',
            'memberSince' => $this->created_at?->toIso8601String(),
            'completion' => ProfileCompletion::for($this->resource),
            'preferences' => $this->whenLoaded(
                'preferences',
                fn () => $this->preferences ? PreferencesResource::make($this->preferences) : null,
            ),
        ];
    }
}

<?php

namespace App\Modules\Profile\Services;

use App\Models\User;
use App\Modules\Profile\Models\UserPreference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Personal information, avatar and preferences.
 */
class ProfileService
{
    /**
     * User with the relations needed by ProfileResource.
     */
    public function forDisplay(User $user): User
    {
        $this->preferencesOf($user);

        return $user->load('preferences');
    }

    /**
     * @param  array<string, mixed>  $attributes  users columns
     */
    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);

        // A new e-mail address has to be verified again.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $this->forDisplay($user);
    }

    public function updateAvatar(User $user, UploadedFile $file): User
    {
        $disk = $this->avatarDisk();
        $previous = $user->avatar_path;

        $path = $file->storeAs(
            'avatars/'.$user->id,
            Str::uuid()->toString().'.'.$file->extension(),
            ['disk' => $disk],
        );

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous) {
            Storage::disk($disk)->delete($previous);
        }

        return $this->forDisplay($user);
    }

    public function removeAvatar(User $user): User
    {
        if ($user->avatar_path) {
            Storage::disk($this->avatarDisk())->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return $this->forDisplay($user);
    }

    public function preferencesOf(User $user): UserPreference
    {
        $preferences = $user->preferences()->firstOrCreate();
        // Lazily created row: not a "creation" from the client's point of view (no 201).
        $preferences->wasRecentlyCreated = false;

        return $preferences;
    }

    /**
     * @param  array<string, mixed>  $attributes  user_preferences columns
     */
    public function updatePreferences(User $user, array $attributes): UserPreference
    {
        $preferences = $this->preferencesOf($user);
        $preferences->update($attributes);

        return $preferences;
    }

    private function avatarDisk(): string
    {
        return config('moncolis.profile.avatar_disk');
    }
}

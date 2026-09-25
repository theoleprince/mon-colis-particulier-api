<?php

namespace App\Modules\Profile\Http\Resources;

use App\Modules\Profile\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserPreference
 */
class PreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'language' => $this->language,
            'theme' => $this->theme,
            'notifyPush' => $this->notify_push,
            'notifySms' => $this->notify_sms,
            'notifyEmail' => $this->notify_email,
            'marketingOptIn' => $this->marketing_opt_in,
        ];
    }
}

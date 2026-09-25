<?php

namespace App\Modules\Identity\Services;

/**
 * Result of an OTP sending, returned to the app to drive the code entry screen.
 */
final class OtpChallenge
{
    public function __construct(
        public readonly string $channel,
        public readonly string $maskedDestination,
        public readonly int $expiresIn,
        public readonly int $resendIn,
        public readonly ?string $devCode = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'channel' => $this->channel,
            'destination' => $this->maskedDestination,
            'expiresIn' => $this->expiresIn,
            'resendIn' => $this->resendIn,
            'devCode' => $this->devCode,
        ], fn ($value) => $value !== null);
    }
}

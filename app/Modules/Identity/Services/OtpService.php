<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Enums\OtpPurpose;
use App\Modules\Identity\Models\OtpCode;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Sms\SmsGateway;
use App\Shared\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private readonly SmsGateway $sms)
    {
    }

    public function send(string $e164Phone, OtpPurpose $purpose, ?User $user = null): OtpChallenge
    {
        $config = config('moncolis.otp');

        $last = $this->pendingQuery($e164Phone, $purpose, $user)->latest('id')->first();
        if ($last !== null) {
            $elapsed = (int) $last->created_at->diffInSeconds(now(), true);
            if ($elapsed < $config['resend_cooldown_seconds']) {
                throw new BusinessException(
                    'Veuillez patienter avant de demander un nouveau code.',
                    'OTP_COOLDOWN',
                    429,
                );
            }
        }

        // A new code invalidates the previous ones.
        $this->pendingQuery($e164Phone, $purpose, $user)->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 10 ** $config['length'] - 1), $config['length'], '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id' => $user?->id,
            'destination' => $e164Phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds($config['ttl_seconds']),
        ]);

        $this->sms->send($e164Phone, $purpose->smsMessage($code, intdiv($config['ttl_seconds'], 60)));

        return new OtpChallenge(
            maskedDestination: PhoneNumber::mask($e164Phone),
            expiresIn: $config['ttl_seconds'],
            resendIn: $config['resend_cooldown_seconds'],
            devCode: $this->exposeCode() ? $code : null,
        );
    }

    /**
     * Consumes the code or throws a BusinessException (expired, invalid, too many attempts).
     */
    public function verify(string $e164Phone, OtpPurpose $purpose, string $code, ?User $user = null): void
    {
        $otp = $this->pendingQuery($e164Phone, $purpose, $user)->latest('id')->first();

        if ($otp === null || $otp->isExpired()) {
            throw new BusinessException('Ce code a expiré. Demandez un nouveau code.', 'OTP_EXPIRED');
        }

        if ($otp->attempts >= config('moncolis.otp.max_attempts')) {
            throw new BusinessException('Trop de tentatives. Demandez un nouveau code.', 'OTP_TOO_MANY_ATTEMPTS', 429);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw new BusinessException('Code incorrect.', 'OTP_INVALID');
        }

        $otp->forceFill(['consumed_at' => now()])->save();
    }

    private function pendingQuery(string $destination, OtpPurpose $purpose, ?User $user)
    {
        return OtpCode::query()
            ->where('destination', $destination)
            ->where('purpose', $purpose)
            ->where('user_id', $user?->id)
            ->whereNull('consumed_at');
    }

    private function exposeCode(): bool
    {
        return config('moncolis.otp.expose_in_response') && app()->environment(['local', 'testing']);
    }
}

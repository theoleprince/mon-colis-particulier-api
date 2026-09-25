<?php

namespace App\Modules\Identity\Mail;

use App\Modules\Identity\Enums\OtpPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * One-time code sent by e-mail (password reset, e-mail verification).
 */
class OtpCodeMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly OtpPurpose $purpose,
        public readonly int $ttlMinutes,
    ) {
    }

    public function build(): static
    {
        $code = e($this->code);
        $message = e($this->purpose->smsMessage($this->code, $this->ttlMinutes));

        return $this->subject($this->purpose->emailSubject())->html(<<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 480px; margin: auto; color: #0E0E10">
              <h2 style="margin-bottom: 4px">MonColis</h2>
              <p>Voici votre code :</p>
              <p style="font-size: 32px; font-weight: bold; letter-spacing: 8px; background: #FFCC00;
                        padding: 12px 20px; border-radius: 12px; display: inline-block">{$code}</p>
              <p style="color: #6E6A61">{$message}</p>
              <p style="color: #6E6A61; font-size: 12px">Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.</p>
            </div>
            HTML);
    }
}

<?php

namespace App\Modules\Identity\Enums;

enum OtpPurpose: string
{
    case PhoneChange = 'phone_change';
    case PhoneVerification = 'phone_verification';
    case Login = 'login';
    case PasswordReset = 'password_reset';
    case EmailVerification = 'email_verification';

    public function smsMessage(string $code, int $ttlMinutes): string
    {
        $action = match ($this) {
            self::PhoneChange => 'changer votre numéro',
            self::PhoneVerification => 'vérifier votre numéro',
            self::Login => 'vous connecter',
            self::PasswordReset => 'réinitialiser votre mot de passe',
            self::EmailVerification => 'vérifier votre adresse e-mail',
        };

        return "MonColis : {$code} est votre code pour {$action}. Valable {$ttlMinutes} min. Ne le partagez avec personne.";
    }

    public function emailSubject(): string
    {
        return match ($this) {
            self::PasswordReset => 'Réinitialisation de votre mot de passe MonColis',
            self::EmailVerification => 'Vérifiez votre adresse e-mail MonColis',
            default => 'Votre code MonColis',
        };
    }
}

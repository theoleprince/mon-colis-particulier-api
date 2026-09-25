<?php

namespace App\Modules\Identity\Enums;

enum OtpPurpose: string
{
    case PhoneChange = 'phone_change';
    case PhoneVerification = 'phone_verification';
    case Login = 'login';
    case PasswordReset = 'password_reset';

    public function smsMessage(string $code, int $ttlMinutes): string
    {
        $action = match ($this) {
            self::PhoneChange => 'changer votre numéro',
            self::PhoneVerification => 'vérifier votre numéro',
            self::Login => 'vous connecter',
            self::PasswordReset => 'réinitialiser votre mot de passe',
        };

        return "MonColis : {$code} est votre code pour {$action}. Valable {$ttlMinutes} min. Ne le partagez avec personne.";
    }
}

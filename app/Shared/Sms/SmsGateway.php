<?php

namespace App\Shared\Sms;

/**
 * SMS sending abstraction (OTP, courier notifications...).
 * Real provider (Orange / MTN / Twilio...) to plug in later; LogSmsGateway in the meantime.
 */
interface SmsGateway
{
    public function send(string $e164Phone, string $message): void;
}

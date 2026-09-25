<?php

namespace App\Shared\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Writes SMS to the application log instead of sending them (dev / until a provider is chosen).
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $e164Phone, string $message): void
    {
        Log::info('[SMS] '.$e164Phone.' : '.$message);
    }
}

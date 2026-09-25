<?php

namespace App\Shared\Support;

/**
 * Phone numbers are stored in E.164 format (+237677897012) so that
 * "677 89 70 12", "00237677897012" and "+237 677897012" match the same account.
 */
final class PhoneNumber
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', trim($raw));

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        if (str_starts_with($digits, '+')) {
            return '+'.ltrim(substr($digits, 1), '+');
        }

        $countryCode = (string) config('moncolis.default_country_code', '237');

        if (str_starts_with($digits, $countryCode) && strlen($digits) > 9) {
            return '+'.$digits;
        }

        return '+'.$countryCode.$digits;
    }

    public static function isValid(?string $raw): bool
    {
        $normalized = self::normalize($raw);

        return $normalized !== null && preg_match('/^\+[1-9]\d{7,14}$/', $normalized) === 1;
    }

    /**
     * "+237677897012" -> "+237 •••• ••12" (safe for display in OTP screens).
     */
    public static function mask(?string $e164): ?string
    {
        if ($e164 === null) {
            return null;
        }

        return substr($e164, 0, 4).' •••• ••'.substr($e164, -2);
    }
}

<?php

namespace Tests\Unit;

use App\Shared\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            'local' => ['677897012', '+237677897012'],
            'local with spaces' => ['677 89 70 12', '+237677897012'],
            'with country code' => ['237677897012', '+237677897012'],
            'international 00' => ['00237677897012', '+237677897012'],
            'e164' => ['+237 677-897-012', '+237677897012'],
            'other country' => ['+22890112233', '+22890112233'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_normalize(string $raw, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($raw));
    }

    public function test_validity_and_mask(): void
    {
        $this->assertTrue(PhoneNumber::isValid('677897012'));
        $this->assertFalse(PhoneNumber::isValid('12'));
        $this->assertFalse(PhoneNumber::isValid('abc'));
        $this->assertNull(PhoneNumber::normalize('  '));
        $this->assertSame('+237 •••• ••12', PhoneNumber::mask('+237677897012'));
    }
}

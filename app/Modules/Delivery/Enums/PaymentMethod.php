<?php

namespace App\Modules\Delivery\Enums;

enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile Money',
            self::Cash => 'Espèces à la livraison',
        };
    }

    /**
     * Cash is collected by the courier: the courier search starts right away.
     */
    public function isPrepaid(): bool
    {
        return $this === self::MobileMoney;
    }
}

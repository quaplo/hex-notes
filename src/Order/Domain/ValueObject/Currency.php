<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use InvalidArgumentException;

enum Currency: string
{
    case CZK = 'CZK';
    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';
    public function getSymbol(): string
    {
        return match ($this) {
            self::CZK => 'Kč',
            self::EUR => '€',
            self::USD => '$',
            self::GBP => '£',
        };
    }
}

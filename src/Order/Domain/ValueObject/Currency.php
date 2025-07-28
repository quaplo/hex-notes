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

    public static function fromString(string $currency): self
    {
        return self::tryFrom($currency) ?? throw new InvalidArgumentException("Invalid currency: {$currency}");
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(Currency $other): bool
    {
        return $this === $other;
    }

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

<?php

declare(strict_types=1);

namespace App\Order\Application\Command;

use App\Order\Domain\ValueObject\Currency;

final readonly class CreateOrderCommand
{
    private function __construct(
        public Currency $currency
    ) {
    }

    public static function fromPrimitives(string $currency): self
    {
        return new self(Currency::fromString($currency));
    }

    public static function withCurrency(Currency $currency): self
    {
        return new self($currency);
    }
}

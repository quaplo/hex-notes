<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use Stringable;
use InvalidArgumentException;

final readonly class Money implements Stringable
{
    public function __construct(
        private float $amount,
        private Currency $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public static function fromAmountAndCurrency(float $amount, string $currency): self
    {
        return new self($amount, Currency::fromString($currency));
    }

    public static function zero(Currency $currency): self
    {
        return new self(0.0, $currency);
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $money): self
    {
        $this->ensureSameCurrency($money);

        return new self($this->amount + $money->amount, $this->currency);
    }

    public function subtract(Money $money): self
    {
        $this->ensureSameCurrency($money);

        $newAmount = $this->amount - $money->amount;
        if ($newAmount < 0) {
            throw new InvalidArgumentException('Cannot subtract to negative amount');
        }

        return new self($newAmount, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }

        return new self($this->amount * $multiplier, $this->currency);
    }

    public function equals(Money $money): bool
    {
        return $this->amount === $money->amount && $this->currency->equals($money->currency);
    }

    public function isGreaterThan(Money $money): bool
    {
        $this->ensureSameCurrency($money);

        return $this->amount > $money->amount;
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    public function toString(): string
    {
        return sprintf('%.2f %s', $this->amount, $this->currency->toString());
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function ensureSameCurrency(Money $money): void
    {
        if (!$this->currency->equals($money->currency)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot operate on different currencies: %s and %s',
                    $this->currency->toString(),
                    $money->currency->toString()
                )
            );
        }
    }
}

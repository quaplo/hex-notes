<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Money
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

    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);
        
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);
        
        $newAmount = $this->amount - $other->amount;
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

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);
        
        return $this->amount > $other->amount;
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

    private function ensureSameCurrency(Money $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot operate on different currencies: %s and %s',
                    $this->currency->toString(),
                    $other->currency->toString()
                )
            );
        }
    }
}
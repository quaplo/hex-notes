<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use InvalidArgumentException;

enum OrderStatus: string
{
    case CREATED = 'CREATED';
    case CONFIRMED = 'CONFIRMED';
    case PAID = 'PAID';
    case SHIPPED = 'SHIPPED';
    case DELIVERED = 'DELIVERED';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';

    public function isCreated(): bool
    {
        return $this === self::CREATED;
    }

    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isShipped(): bool
    {
        return $this === self::SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public function canBeModified(): bool
    {
        return in_array($this, [self::CREATED, self::CONFIRMED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::CREATED, self::CONFIRMED, self::PAID], true);
    }

    public static function fromString(string $status): self
    {
        return self::tryFrom($status) ?? throw new InvalidArgumentException("Invalid order status: {$status}");
    }

    public function toString(): string
    {
        return $this->value;
    }
}

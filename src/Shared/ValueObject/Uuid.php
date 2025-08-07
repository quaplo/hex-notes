<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final readonly class Uuid implements Stringable
{
    public function __construct(private string $value)
    {
        if (!SymfonyUuid::isValid($this->value)) {
            throw new InvalidArgumentException("Invalid UUID: {$this->value}");
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(SymfonyUuid::v4()->toRfc4122());
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $uuid): bool
    {
        return $this->value === (string) $uuid;
    }
}

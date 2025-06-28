<?php

declare(strict_types=1);

namespace App\Domain\Project\ValueObject;

use InvalidArgumentException;

final class ProjectRole
{
    public const OWNER = 'owner';
    public const PARTICIPANT = 'participant';

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, [self::OWNER, self::PARTICIPANT], true)) {
            throw new InvalidArgumentException("Invalid project role: $value");
        }

        $this->value = $value;
    }

    public static function owner(): self
    {
        return new self(self::OWNER);
    }

    public static function participant(): self
    {
        return new self(self::PARTICIPANT);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public static function from(string $value): self
    {
        return new self($value); // validácia prebehne v konštruktore
    }
}

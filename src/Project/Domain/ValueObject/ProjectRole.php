<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use InvalidArgumentException;

final class ProjectRole
{
    public const OWNER = 'owner';
    public const PARTICIPANT = 'participant';

    private string $value;

    private function __construct(string $value)
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

    public static function create(string $value): self
    {
        return new self($value); // validácia prebehne v konštruktore
    }
}

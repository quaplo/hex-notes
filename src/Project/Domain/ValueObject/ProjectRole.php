<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use InvalidArgumentException;

enum ProjectRole: string
{
    case OWNER = 'owner';
    case PARTICIPANT = 'participant';

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }

    public static function create(string $value): self
    {
        return match ($value) {
            self::OWNER->value => self::OWNER,
            self::PARTICIPANT->value => self::PARTICIPANT,
            default => throw new InvalidArgumentException("Invalid project role: $value"),
        };
    }
}

<?php

namespace App\Project\Domain\ValueObject;

use App\Shared\ValueObject\Uuid;

final class UserId
{
    public function __construct(public readonly Uuid $value)
    {
    }

    public static function fromUuid(Uuid $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value->__toString();
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::create($id));
    }

    public static function generate(): self
    {
        return new self(Uuid::generate());
    }
}

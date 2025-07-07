<?php

namespace App\Project\Domain\ValueObject;

use Stringable;
use InvalidArgumentException;

final readonly class ProjectName implements Stringable
{
    public function __construct(private string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Project name cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

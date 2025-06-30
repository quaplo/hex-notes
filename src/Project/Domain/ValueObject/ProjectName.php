<?php

namespace App\Project\Domain\ValueObject;

use InvalidArgumentException;

final class ProjectName
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

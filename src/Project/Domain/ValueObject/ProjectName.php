<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

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

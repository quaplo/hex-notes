<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RenameProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Project name cannot be empty')]
        #[Assert\Length(min: 3, max: 100, minMessage: 'Project name must be at least 3 characters', maxMessage: 'Project name cannot exceed 100 characters')]
        public string $name
    ) {
    }
}

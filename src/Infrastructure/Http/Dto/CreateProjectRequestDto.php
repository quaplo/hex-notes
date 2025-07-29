<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Project name cannot be empty')]
        #[Assert\Length(
            min: 3,
            max: 100,
            minMessage: 'Project name must be at least 3 characters',
            maxMessage: 'Project name cannot exceed 100 characters'
        )]
        public string $name,
        #[Assert\Length(max: 500, maxMessage: 'Description cannot exceed 500 characters')]
        public ?string $description = null,
        #[Assert\Uuid(message: 'Owner ID must be a valid UUID')]
        public ?string $ownerId = null
    ) {
    }
}

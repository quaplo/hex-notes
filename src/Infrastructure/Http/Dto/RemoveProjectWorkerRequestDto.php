<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RemoveProjectWorkerRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'User ID cannot be empty')]
        #[Assert\Uuid(message: 'User ID must be a valid UUID')]
        public string $userId,
        #[Assert\Uuid(message: 'Removed by must be a valid UUID')]
        public ?string $removedBy = null,
    ) {
    }
}

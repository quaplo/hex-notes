<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AddProjectWorkerRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'User ID cannot be empty')]
        #[Assert\Uuid(message: 'User ID must be a valid UUID')]
        public readonly string $userId,
        
        #[Assert\NotBlank(message: 'Role cannot be empty')]
        #[Assert\Choice(choices: ['owner', 'participant'], message: 'Role must be one of: owner, participant')]
        public readonly string $role,
        
        #[Assert\Uuid(message: 'Added by must be a valid UUID')]
        public readonly ?string $addedBy = null
    ) {
    }
}

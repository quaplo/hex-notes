<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be empty')]
        #[Assert\Email(message: 'Please provide a valid email address')]
        #[Assert\Length(max: 255, maxMessage: 'Email cannot exceed 255 characters')]
        public readonly string $email
    ) {
    }
}

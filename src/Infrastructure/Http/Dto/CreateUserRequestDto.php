<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateUserRequest',
    description: 'Request pre vytvorenie nového používateľa',
    type: 'object',
    required: ['email']
)]
final readonly class CreateUserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email cannot be empty')]
        #[Assert\Email(message: 'Please provide a valid email address')]
        #[Assert\Length(max: 255, maxMessage: 'Email cannot exceed 255 characters')]
        #[OA\Property(
            property: 'email',
            description: 'Email adresa používateľa',
            type: 'string',
            format: 'email',
            maxLength: 255,
            example: 'user@example.com'
        )]
        public string $email,
    ) {
    }
}

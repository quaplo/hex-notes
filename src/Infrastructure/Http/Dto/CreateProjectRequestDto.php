<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateProjectRequest',
    description: 'Request pre vytvorenie nového projektu',
    type: 'object',
    required: ['name']
)]
final readonly class CreateProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Project name cannot be empty')]
        #[Assert\Length(min: 3, max: 100, minMessage: 'Project name must be at least 3 characters', maxMessage: 'Project name cannot exceed 100 characters')]
        #[OA\Property(
            property: 'name',
            description: 'Názov projektu',
            type: 'string',
            minLength: 3,
            maxLength: 100,
            example: 'Môj nový projekt'
        )]
        public string $name,
        #[Assert\Length(max: 500, maxMessage: 'Description cannot exceed 500 characters')]
        #[OA\Property(
            property: 'description',
            description: 'Popis projektu (voliteľné)',
            type: 'string',
            maxLength: 500,
            nullable: true,
            example: 'Detailný popis projektu a jeho cieľov'
        )]
        public ?string $description = null,
        #[Assert\Uuid(message: 'Owner ID must be a valid UUID')]
        #[OA\Property(
            property: 'ownerId',
            description: 'UUID vlastníka projektu (voliteľné)',
            type: 'string',
            format: 'uuid',
            nullable: true,
            example: '550e8400-e29b-41d4-a716-446655440001'
        )]
        public ?string $ownerId = null,
    ) {
    }
}

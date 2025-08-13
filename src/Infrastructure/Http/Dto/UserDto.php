<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResponse',
    description: 'Response s údajmi používateľa',
    type: 'object'
)]
final readonly class UserDto
{
    public function __construct(
        #[OA\Property(
            property: 'id',
            description: 'Unikátny identifikátor používateľa',
            type: 'string',
            format: 'uuid',
            example: '550e8400-e29b-41d4-a716-446655440000'
        )]
        public string $id,
        #[OA\Property(
            property: 'email',
            description: 'Email adresa používateľa',
            type: 'string',
            format: 'email',
            example: 'user@example.com'
        )]
        public string $email,
        #[OA\Property(
            property: 'createdAt',
            description: 'Dátum a čas vytvorenia používateľa',
            type: 'string',
            format: 'date-time',
            example: '2024-01-15T10:30:00Z'
        )]
        public string $createdAt,
    ) {
    }
}

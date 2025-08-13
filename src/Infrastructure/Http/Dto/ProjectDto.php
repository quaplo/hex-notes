<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProjectResponse',
    description: 'Response s údajmi projektu',
    type: 'object'
)]
final readonly class ProjectDto
{
    public function __construct(
        #[OA\Property(
            property: 'id',
            description: 'Unikátny identifikátor projektu',
            type: 'string',
            format: 'uuid',
            example: '550e8400-e29b-41d4-a716-446655440000'
        )]
        public string $id,
        #[OA\Property(
            property: 'name',
            description: 'Názov projektu',
            type: 'string',
            example: 'Môj projekt'
        )]
        public string $name,
        #[OA\Property(
            property: 'ownerId',
            description: 'UUID vlastníka projektu',
            type: 'string',
            format: 'uuid',
            example: '550e8400-e29b-41d4-a716-446655440001'
        )]
        public string $ownerId,
        #[OA\Property(
            property: 'createdAt',
            description: 'Dátum a čas vytvorenia projektu',
            type: 'string',
            format: 'date-time',
            example: '2024-01-15T10:30:00Z'
        )]
        public string $createdAt,
        #[OA\Property(
            property: 'deletedAt',
            description: 'Dátum a čas zmazania projektu (ak je zmazaný)',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: null
        )]
        public ?string $deletedAt = null,
    ) {
    }
}

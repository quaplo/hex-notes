<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class ProjectDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $ownerId,
        public string $createdAt,
        public ?string $deletedAt = null
    ) {
    }
}

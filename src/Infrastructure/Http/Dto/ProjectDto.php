<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final class ProjectDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $ownerId,
        public readonly string $createdAt,
        public readonly ?string $deletedAt = null
    ) {}
}

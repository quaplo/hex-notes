<?php

declare(strict_types=1);

namespace App\Shared\Application\Dto;

final readonly class ProjectDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $ownerId,
        public array $workers = [],
        public bool $isDeleted = false
    ) {
    }
}

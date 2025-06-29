<?php

namespace App\Infrastructure\Http\Dto;

final class ProjectDto
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $ownerEmail,
        public string $createdAt,
    ) {
    }
}

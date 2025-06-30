<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final class CreateProjectRequestDto
{
    public function __construct(
        public string $name,
        public string $ownerId
    ) {}
}

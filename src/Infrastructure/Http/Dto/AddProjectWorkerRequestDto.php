<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final class AddProjectWorkerRequestDto
{
    public function __construct(
        public string $userId,
        public string $role,
        public ?string $addedBy = null
    ) {
    }
}

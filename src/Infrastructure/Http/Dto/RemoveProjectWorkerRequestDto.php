<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final class RemoveProjectWorkerRequestDto
{
    public function __construct(
        public string $userId,
        public ?string $removedBy = null
    ) {}
} 
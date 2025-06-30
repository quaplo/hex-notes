<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final class UserDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $createdAt
    ) {
    }
} 
<?php

declare(strict_types=1);

namespace App\Shared\Application\Dto;

final readonly class UserDto
{
    public function __construct(
        public string $id,
        public string $email,
        public bool $isDeleted = false
    ) {
    }
}
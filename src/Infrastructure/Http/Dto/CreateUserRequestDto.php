<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final class CreateUserRequestDto
{
    public function __construct(
        public readonly string $email
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

final readonly class CreateProjectRequest
{
    public function __construct(
        public string $name,
        public string $ownerEmail,
    ) {
    }
}

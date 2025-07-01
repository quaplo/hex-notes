<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final readonly class RegisterProjectCommand
{
    public function __construct(
        public string $name,
        public string $ownerId
    ) {
    }
}

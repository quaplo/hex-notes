<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final class RegisterProjectCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $ownerId
    ) {
    }
}

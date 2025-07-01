<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final readonly class AddProjectWorkerCommand
{
    public function __construct(
        public string $projectId,
        public string $userId,
        public string $role,
        public ?string $addedBy = null
    ) {
    }
}

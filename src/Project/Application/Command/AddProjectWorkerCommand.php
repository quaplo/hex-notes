<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final class AddProjectWorkerCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $userId,
        public readonly string $role,
        public readonly ?string $addedBy = null
    ) {}
} 
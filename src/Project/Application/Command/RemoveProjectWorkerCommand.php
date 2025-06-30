<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final class RemoveProjectWorkerCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $userId,
        public readonly ?string $removedBy = null
    ) {
    }
}

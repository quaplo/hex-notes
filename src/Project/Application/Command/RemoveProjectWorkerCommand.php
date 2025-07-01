<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

final readonly class RemoveProjectWorkerCommand
{
    public function __construct(
        public string $projectId,
        public string $userId,
        public ?string $removedBy = null
    ) {
    }
}

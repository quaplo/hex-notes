<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Worker;

use App\Shared\ValueObject\Uuid;

final readonly class RemoveProjectWorkerCommand
{
    private function __construct(
        public Uuid $projectId,
        public Uuid $userId,
        public ?Uuid $removedBy = null,
    ) {
    }

    public static function fromPrimitives(string $projectId, string $userId, ?string $removedBy = null): self
    {
        return new self(
            Uuid::create($projectId),
            Uuid::create($userId),
            $removedBy ? Uuid::create($removedBy) : null
        );
    }
}

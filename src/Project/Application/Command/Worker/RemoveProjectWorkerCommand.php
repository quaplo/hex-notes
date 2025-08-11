<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Worker;

use App\Shared\ValueObject\Uuid;

final readonly class RemoveProjectWorkerCommand
{
    private function __construct(
        private Uuid $projectId,
        private Uuid $userId,
        private ?Uuid $removedBy = null,
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getRemovedBy(): ?Uuid
    {
        return $this->removedBy;
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

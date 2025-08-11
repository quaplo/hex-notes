<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Worker;

use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\ValueObject\Uuid;

final readonly class AddProjectWorkerCommand
{
    private function __construct(
        private Uuid $projectId,
        private Uuid $userId,
        private ProjectRole $role,
        private Uuid $addedBy,
    ) {
    }

    public static function fromPrimitives(string $projectId, string $userId, string $role, string $addedBy): self
    {
        return new self(
            Uuid::create($projectId),
            Uuid::create($userId),
            ProjectRole::create($role),
            Uuid::create($addedBy)
        );
    }

    public function getRole(): ProjectRole
    {
        return $this->role;
    }

    public function getAddedBy(): Uuid
    {
        return $this->addedBy;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }
}

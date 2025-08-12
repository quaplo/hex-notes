<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Delete;

use App\Shared\ValueObject\Uuid;

final readonly class DeleteProjectCommand
{
    private function __construct(
        private Uuid $projectId,
        private Uuid $userId,
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

    public static function fromPrimitives(string $projectId, string $userId): self
    {
        return new self(Uuid::create($projectId), Uuid::create($userId));
    }
}

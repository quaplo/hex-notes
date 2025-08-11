<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Rename;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;

final readonly class RenameProjectCommand
{
    private function __construct(
        private Uuid $projectId,
        private ProjectName $newName,
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getNewName(): ProjectName
    {
        return $this->newName;
    }

    public static function fromPrimitives(string $projectId, string $newName): self
    {
        return new self(
            Uuid::create($projectId),
            new ProjectName($newName)
        );
    }
}

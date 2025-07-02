<?php

declare(strict_types=1);

namespace App\Project\Application\Command;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;

final readonly class RenameProjectCommand
{
    private function __construct(
        public Uuid $projectId,
        public ProjectName $newName
    ) {
    }

    public static function fromPrimitives(string $projectId, string $newName): self
    {
        return new self(
            Uuid::create($projectId),
            new ProjectName($newName)
        );
    }
}
<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Delete;

use App\Shared\ValueObject\Uuid;

final readonly class DeleteProjectCommand
{
    private function __construct(
        private Uuid $projectId,
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public static function fromPrimitives(string $projectId): self
    {
        return new self(Uuid::create($projectId));
    }
}

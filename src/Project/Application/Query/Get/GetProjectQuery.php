<?php

declare(strict_types=1);

namespace App\Project\Application\Query\Get;

use App\Shared\ValueObject\Uuid;

final readonly class GetProjectQuery
{
    private function __construct(
        private Uuid $projectId,
    ) {
    }

    public static function fromPrimitives(string $projectId): self
    {
        return new self(Uuid::create($projectId));
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }
}

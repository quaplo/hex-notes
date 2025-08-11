<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Delete;

use App\Shared\ValueObject\Uuid;

final readonly class DeleteOrphanedProjectsCommand
{
    private function __construct(
        private Uuid $deletedUserId,
    ) {
    }

    public static function fromPrimitives(string $deletedUserId): self
    {
        return new self(Uuid::create($deletedUserId));
    }

    public function getDeletedUserId(): Uuid
    {
        return $this->deletedUserId;
    }
}

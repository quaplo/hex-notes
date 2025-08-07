<?php

declare(strict_types=1);

namespace App\Project\Application\Command\Delete;

use App\Shared\ValueObject\Uuid;

final readonly class DeleteOrphanedProjectsCommand
{
    public function __construct(
        private Uuid $uuid,
    ) {
    }

    public function getDeletedUserId(): Uuid
    {
        return $this->uuid;
    }
}

<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectRenamedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $projectId,
        private readonly ProjectName $oldName,
        private readonly ProjectName $newName,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getOldName(): ProjectName
    {
        return $this->oldName;
    }

    public function getNewName(): ProjectName
    {
        return $this->newName;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

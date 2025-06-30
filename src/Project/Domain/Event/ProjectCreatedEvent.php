<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectOwner;
use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectCreatedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $projectId,
        private readonly ProjectName $name,
        private readonly ProjectOwner $owner,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getName(): ProjectName
    {
        return $this->name;
    }

    public function getOwner(): ProjectOwner
    {
        return $this->owner;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
} 
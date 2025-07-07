<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectRenamedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $uuid,
        private ProjectName $oldName,
        private ProjectName $newName,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->uuid;
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

    public function getAggregateId(): string
    {
        return $this->uuid->toString();
    }

    public function getEventName(): string
    {
        return 'project.renamed';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->uuid->toString(),
            'oldName' => $this->oldName->__toString(),
            'newName' => $this->newName->__toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            new ProjectName($eventData['oldName']),
            new ProjectName($eventData['newName']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}

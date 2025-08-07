<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectCreatedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $projectId,
        private ProjectName $projectName,
        private Uuid $ownerId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getName(): ProjectName
    {
        return $this->projectName;
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): string
    {
        return $this->projectId->toString();
    }

    public function getEventName(): string
    {
        return 'project.created';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->projectId->toString(),
            'name' => $this->projectName->__toString(),
            'ownerId' => $this->ownerId->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            new ProjectName($eventData['name']),
            Uuid::create($eventData['ownerId']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}

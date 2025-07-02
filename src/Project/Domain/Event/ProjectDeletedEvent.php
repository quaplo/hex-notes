<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Shared\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectDeletedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $projectId,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
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
        return 'project.deleted';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->projectId->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}

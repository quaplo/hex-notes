<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectDeletedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $uuid,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->uuid;
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
        return 'project.deleted';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->uuid->toString(),
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

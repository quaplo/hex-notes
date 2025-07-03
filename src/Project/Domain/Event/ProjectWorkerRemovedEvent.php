<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final class ProjectWorkerRemovedEvent implements DomainEvent
{
    public function __construct(
        private readonly Uuid $projectId,
        private readonly Uuid $userId,
        private readonly ?Uuid $removedBy = null,
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getRemovedBy(): ?Uuid
    {
        return $this->removedBy;
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
        return 'project.worker_removed';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->projectId->toString(),
            'userId' => $this->userId->toString(),
            'removedBy' => $this->removedBy?->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            Uuid::create($eventData['userId']),
            isset($eventData['removedBy']) ? Uuid::create($eventData['removedBy']) : null,
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}

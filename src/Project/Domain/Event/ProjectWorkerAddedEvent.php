<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectWorkerAddedEvent implements DomainEvent
{
    public function __construct(
        private Uuid $projectId,
        private Uuid $userId,
        private ProjectRole $projectRole,
        private ?Uuid $addedBy = null,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable()
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

    public function getRole(): ProjectRole
    {
        return $this->projectRole;
    }

    public function getAddedBy(): ?Uuid
    {
        return $this->addedBy;
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
        return 'project.worker_added';
    }

    public function getEventData(): array
    {
        return [
            'projectId' => $this->projectId->toString(),
            'userId' => $this->userId->toString(),
            'role' => $this->projectRole->toString(),
            'addedBy' => $this->addedBy?->toString(),
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            Uuid::create($eventData['userId']),
            ProjectRole::create($eventData['role']),
            isset($eventData['addedBy']) ? Uuid::create($eventData['addedBy']) : null,
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}

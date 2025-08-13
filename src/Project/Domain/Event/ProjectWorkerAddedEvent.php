<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectWorkerAddedEvent extends ProjectEvent
{
    public function __construct(
        Uuid $projectId,
        private Uuid $userId,
        private ProjectRole $projectRole,
        private ?Uuid $addedBy = null,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($projectId, $occurredAt);
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

    public function getEventName(): string
    {
        return 'project.worker_added';
    }

    public function getEventData(): array
    {
        return array_merge($this->getBaseEventData(), [
            'userId' => $this->userId->toString(),
            'role' => $this->projectRole->toString(),
            'addedBy' => $this->addedBy?->toString(),
        ]);
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

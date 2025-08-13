<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectWorkerRemovedEvent extends ProjectEvent
{
    public function __construct(
        Uuid $projectId,
        private Uuid $userId,
        private ?Uuid $removedBy = null,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($projectId, $occurredAt);
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getRemovedBy(): ?Uuid
    {
        return $this->removedBy;
    }

    public function getEventName(): string
    {
        return 'project.worker_removed';
    }

    public function getEventData(): array
    {
        return array_merge($this->getBaseEventData(), [
            'userId' => $this->userId->toString(),
            'removedBy' => $this->removedBy?->toString(),
        ]);
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

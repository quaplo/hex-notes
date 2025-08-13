<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectDeletedEvent extends ProjectEvent
{
    public function __construct(
        Uuid $projectId,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($projectId, $occurredAt);
    }

    public function getEventName(): string
    {
        return 'project.deleted';
    }

    public function getEventData(): array
    {
        return $this->getBaseEventData();
    }

    public static function fromEventData(array $eventData): self
    {
        return new self(
            Uuid::create($eventData['projectId']),
            new DateTimeImmutable($eventData['occurredAt'])
        );
    }
}

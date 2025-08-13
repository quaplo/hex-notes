<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectCreatedEvent extends ProjectEvent
{
    public function __construct(
        Uuid $projectId,
        private ProjectName $projectName,
        private Uuid $ownerId,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($projectId, $occurredAt);
    }

    public function getName(): ProjectName
    {
        return $this->projectName;
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }

    public function getEventName(): string
    {
        return 'project.created';
    }

    public function getEventData(): array
    {
        return array_merge($this->getBaseEventData(), [
            'name' => $this->projectName->__toString(),
            'ownerId' => $this->ownerId->toString(),
        ]);
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

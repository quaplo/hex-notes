<?php

declare(strict_types=1);

namespace App\Project\Domain\Event;

use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class ProjectRenamedEvent extends ProjectEvent
{
    public function __construct(
        Uuid $projectId,
        private ProjectName $oldName,
        private ProjectName $newName,
        DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {
        parent::__construct($projectId, $occurredAt);
    }

    public function getOldName(): ProjectName
    {
        return $this->oldName;
    }

    public function getNewName(): ProjectName
    {
        return $this->newName;
    }

    public function getEventName(): string
    {
        return 'project.renamed';
    }

    public function getEventData(): array
    {
        return array_merge($this->getBaseEventData(), [
            'oldName' => $this->oldName->__toString(),
            'newName' => $this->newName->__toString(),
        ]);
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

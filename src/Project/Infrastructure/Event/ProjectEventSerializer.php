<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Event;

use RuntimeException;
use DateTimeInterface;
use DateTimeImmutable;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Project\Domain\Event\ProjectDeletedEvent;
use App\Project\Domain\Event\ProjectRenamedEvent;
use App\Project\Domain\Event\ProjectWorkerAddedEvent;
use App\Project\Domain\Event\ProjectWorkerRemovedEvent;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectRole;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventSerializer;
use App\Shared\ValueObject\Uuid;
use JsonException;

final class ProjectEventSerializer implements EventSerializer
{
    private const array SUPPORTED_EVENTS = [
        ProjectCreatedEvent::class,
        ProjectRenamedEvent::class,
        ProjectDeletedEvent::class,
        ProjectWorkerAddedEvent::class,
        ProjectWorkerRemovedEvent::class,
    ];

    public function supports(string $eventType): bool
    {
        return in_array($eventType, self::SUPPORTED_EVENTS, true);
    }

    public function serialize(DomainEvent $domainEvent): string
    {
        try {
            return match ($domainEvent::class) {
                ProjectCreatedEvent::class => $this->serializeProjectCreatedEvent($domainEvent),
                ProjectRenamedEvent::class => $this->serializeProjectRenamedEvent($domainEvent),
                ProjectDeletedEvent::class => $this->serializeProjectDeletedEvent($domainEvent),
                ProjectWorkerAddedEvent::class => $this->serializeProjectWorkerAddedEvent($domainEvent),
                ProjectWorkerRemovedEvent::class => $this->serializeProjectWorkerRemovedEvent($domainEvent),
                default => throw new RuntimeException(
                    "Unsupported event type for serialization: " . $domainEvent::class
                )
            };
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to serialize event', 0, $e);
        }
    }

    public function deserialize(string $eventData, string $eventType): DomainEvent
    {
        try {
            $data = json_decode($eventData, true, 512, JSON_THROW_ON_ERROR);

            return match ($eventType) {
                ProjectCreatedEvent::class => $this->deserializeProjectCreatedEvent($data),
                ProjectRenamedEvent::class => $this->deserializeProjectRenamedEvent($data),
                ProjectDeletedEvent::class => $this->deserializeProjectDeletedEvent($data),
                ProjectWorkerAddedEvent::class => $this->deserializeProjectWorkerAddedEvent($data),
                ProjectWorkerRemovedEvent::class => $this->deserializeProjectWorkerRemovedEvent($data),
                default => throw new RuntimeException("Unsupported event type for deserialization: $eventType")
            };
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to deserialize event', 0, $e);
        }
    }

    private function serializeProjectCreatedEvent(ProjectCreatedEvent $projectCreatedEvent): string
    {
        $data = [
            'projectId' => $projectCreatedEvent->getProjectId()->toString(),
            'name' => $projectCreatedEvent->getName()->__toString(),
            'ownerId' => $projectCreatedEvent->getOwnerId()->toString(),
            'occurredAt' => $projectCreatedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectRenamedEvent(ProjectRenamedEvent $projectRenamedEvent): string
    {
        $data = [
            'projectId' => $projectRenamedEvent->getProjectId()->toString(),
            'oldName' => $projectRenamedEvent->getOldName()->__toString(),
            'newName' => $projectRenamedEvent->getNewName()->__toString(),
            'occurredAt' => $projectRenamedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectDeletedEvent(ProjectDeletedEvent $projectDeletedEvent): string
    {
        $data = [
            'projectId' => $projectDeletedEvent->getProjectId()->toString(),
            'occurredAt' => $projectDeletedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectWorkerAddedEvent(ProjectWorkerAddedEvent $projectWorkerAddedEvent): string
    {
        $data = [
            'projectId' => $projectWorkerAddedEvent->getProjectId()->toString(),
            'userId' => $projectWorkerAddedEvent->getUserId()->toString(),
            'role' => (string)$projectWorkerAddedEvent->getRole(),
            'addedBy' => $projectWorkerAddedEvent->getAddedBy()?->toString(),
            'occurredAt' => $projectWorkerAddedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectWorkerRemovedEvent(ProjectWorkerRemovedEvent $projectWorkerRemovedEvent): string
    {
        $data = [
            'projectId' => $projectWorkerRemovedEvent->getProjectId()->toString(),
            'userId' => $projectWorkerRemovedEvent->getUserId()->toString(),
            'removedBy' => $projectWorkerRemovedEvent->getRemovedBy()?->toString(),
            'occurredAt' => $projectWorkerRemovedEvent->getOccurredAt()->format(DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function deserializeProjectCreatedEvent(array $data): ProjectCreatedEvent
    {
        return new ProjectCreatedEvent(
            Uuid::create($data['projectId']),
            new ProjectName($data['name']),
            Uuid::create($data['ownerId']),
            new DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectRenamedEvent(array $data): ProjectRenamedEvent
    {
        return new ProjectRenamedEvent(
            Uuid::create($data['projectId']),
            new ProjectName($data['oldName']),
            new ProjectName($data['newName']),
            new DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectDeletedEvent(array $data): ProjectDeletedEvent
    {
        return new ProjectDeletedEvent(
            Uuid::create($data['projectId']),
            new DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectWorkerAddedEvent(array $data): ProjectWorkerAddedEvent
    {
        return new ProjectWorkerAddedEvent(
            Uuid::create($data['projectId']),
            Uuid::create($data['userId']),
            ProjectRole::create($data['role']),
            isset($data['addedBy']) && $data['addedBy'] ? Uuid::create($data['addedBy']) : null,
            new DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectWorkerRemovedEvent(array $data): ProjectWorkerRemovedEvent
    {
        return new ProjectWorkerRemovedEvent(
            Uuid::create($data['projectId']),
            Uuid::create($data['userId']),
            isset($data['removedBy']) && $data['removedBy'] ? Uuid::create($data['removedBy']) : null,
            new DateTimeImmutable($data['occurredAt'])
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Event;

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
    private const SUPPORTED_EVENTS = [
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

    public function serialize(DomainEvent $event): string
    {
        try {
            return match (get_class($event)) {
                ProjectCreatedEvent::class => $this->serializeProjectCreatedEvent($event),
                ProjectRenamedEvent::class => $this->serializeProjectRenamedEvent($event),
                ProjectDeletedEvent::class => $this->serializeProjectDeletedEvent($event),
                ProjectWorkerAddedEvent::class => $this->serializeProjectWorkerAddedEvent($event),
                ProjectWorkerRemovedEvent::class => $this->serializeProjectWorkerRemovedEvent($event),
                default => throw new \RuntimeException("Unsupported event type for serialization: " . get_class($event))
            };
        } catch (JsonException $e) {
            throw new \RuntimeException('Failed to serialize event', 0, $e);
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
                default => throw new \RuntimeException("Unsupported event type for deserialization: $eventType")
            };
        } catch (JsonException $e) {
            throw new \RuntimeException('Failed to deserialize event', 0, $e);
        }
    }

    private function serializeProjectCreatedEvent(ProjectCreatedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'name' => $event->getName()->__toString(),
            'ownerId' => $event->getOwnerId()->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectRenamedEvent(ProjectRenamedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'oldName' => $event->getOldName()->__toString(),
            'newName' => $event->getNewName()->__toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectDeletedEvent(ProjectDeletedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectWorkerAddedEvent(ProjectWorkerAddedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString(),
            'role' => (string)$event->getRole(),
            'addedBy' => $event->getAddedBy()?->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectWorkerRemovedEvent(ProjectWorkerRemovedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString(),
            'removedBy' => $event->getRemovedBy()?->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function deserializeProjectCreatedEvent(array $data): ProjectCreatedEvent
    {
        return new ProjectCreatedEvent(
            Uuid::create($data['projectId']),
            new ProjectName($data['name']),
            Uuid::create($data['ownerId']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectRenamedEvent(array $data): ProjectRenamedEvent
    {
        return new ProjectRenamedEvent(
            Uuid::create($data['projectId']),
            new ProjectName($data['oldName']),
            new ProjectName($data['newName']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectDeletedEvent(array $data): ProjectDeletedEvent
    {
        return new ProjectDeletedEvent(
            Uuid::create($data['projectId']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectWorkerAddedEvent(array $data): ProjectWorkerAddedEvent
    {
        return new ProjectWorkerAddedEvent(
            Uuid::create($data['projectId']),
            Uuid::create($data['userId']),
            ProjectRole::create($data['role']),
            isset($data['addedBy']) && $data['addedBy'] ? Uuid::create($data['addedBy']) : null,
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectWorkerRemovedEvent(array $data): ProjectWorkerRemovedEvent
    {
        return new ProjectWorkerRemovedEvent(
            Uuid::create($data['projectId']),
            Uuid::create($data['userId']),
            isset($data['removedBy']) && $data['removedBy'] ? Uuid::create($data['removedBy']) : null,
            new \DateTimeImmutable($data['occurredAt'])
        );
    }
}
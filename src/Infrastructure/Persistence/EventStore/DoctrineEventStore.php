<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventStore;
use App\Shared\ValueObject\Uuid;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use JsonException;

final class DoctrineEventStore implements EventStore
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function append(Uuid $aggregateId, array $events, int $expectedVersion): void
    {
        $this->connection->beginTransaction();

        try {
            // Check optimistic concurrency
            $currentVersion = $this->getCurrentVersion($aggregateId);

            if ($currentVersion !== $expectedVersion) {
                throw new \RuntimeException(
                    sprintf(
                        'Concurrency conflict: expected version %d, got %d',
                        $expectedVersion,
                        $currentVersion
                    )
                );
            }

            $nextVersion = $expectedVersion + 1;

            foreach ($events as $event) {
                $this->insertEvent($aggregateId, $event, $nextVersion++);
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function getEvents(Uuid $aggregateId): array
    {
        $sql = 'SELECT event_data, event_type, version FROM event_store 
                WHERE aggregate_id = ? 
                ORDER BY version ASC';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $aggregateId->toString(), Types::STRING);
        $result = $stmt->executeQuery();

        $events = [];
        while ($row = $result->fetchAssociative()) {
            $events[] = $this->deserializeEvent($row['event_data'], $row['event_type']);
        }

        return $events;
    }

    public function getEventsFromVersion(Uuid $aggregateId, int $fromVersion): array
    {
        $sql = 'SELECT event_data, event_type, version FROM event_store 
                WHERE aggregate_id = ? AND version >= ? 
                ORDER BY version ASC';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $aggregateId->toString(), Types::STRING);
        $stmt->bindValue(2, $fromVersion, Types::INTEGER);
        $result = $stmt->executeQuery();

        $events = [];
        while ($row = $result->fetchAssociative()) {
            $events[] = $this->deserializeEvent($row['event_data'], $row['event_type']);
        }

        return $events;
    }

    public function findProjectAggregatesByOwnerId(Uuid $ownerId): array
    {
        // PostgreSQL syntax for JSON extraction
        $sql = 'SELECT DISTINCT aggregate_id FROM event_store
                WHERE event_type = ? AND event_data->>\'ownerId\' = ?';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, 'App\\Project\\Domain\\Event\\ProjectCreatedEvent', Types::STRING);
        $stmt->bindValue(2, $ownerId->toString(), Types::STRING);
        $result = $stmt->executeQuery();

        $aggregateIds = [];
        while ($row = $result->fetchAssociative()) {
            $aggregateIds[] = Uuid::create($row['aggregate_id']);
        }

        return $aggregateIds;
    }

    private function getCurrentVersion(Uuid $aggregateId): int
    {
        $sql = 'SELECT MAX(version) as version FROM event_store WHERE aggregate_id = ?';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $aggregateId->toString(), Types::STRING);
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();

        return $row['version'] ?? 0;
    }

    private function insertEvent(Uuid $aggregateId, DomainEvent $event, int $version): void
    {
        $sql = 'INSERT INTO event_store (aggregate_id, event_type, event_data, version, occurred_at) 
                VALUES (?, ?, ?, ?, ?)';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $aggregateId->toString(), Types::STRING);
        $stmt->bindValue(2, get_class($event), Types::STRING);
        $stmt->bindValue(3, $this->serializeEvent($event), Types::TEXT);
        $stmt->bindValue(4, $version, Types::INTEGER);
        $stmt->bindValue(5, $event->getOccurredAt(), Types::DATETIME_IMMUTABLE);

        $stmt->executeStatement();
    }

    private function serializeEvent(DomainEvent $event): string
    {
        return match (get_class($event)) {
            'App\\Project\\Domain\\Event\\ProjectCreatedEvent' => $this->serializeProjectCreatedEvent($event),
            'App\\Project\\Domain\\Event\\ProjectRenamedEvent' => $this->serializeProjectRenamedEvent($event),
            'App\\Project\\Domain\\Event\\ProjectDeletedEvent' => $this->serializeProjectDeletedEvent($event),
            'App\\Project\\Domain\\Event\\ProjectWorkerAddedEvent' => $this->serializeProjectWorkerAddedEvent($event),
            'App\\User\\Domain\\Event\\UserCreatedEvent' => $this->serializeUserCreatedEvent($event),
            'App\\User\\Domain\\Event\\UserDeletedEvent' => $this->serializeUserDeletedEvent($event),
            'App\\Project\\Domain\\Event\\ProjectWorkerRemovedEvent' => $this->serializeProjectWorkerRemovedEvent($event),
            default => throw new \RuntimeException("Unknown event type for serialization: " . get_class($event))
        };
    }

    private function serializeProjectCreatedEvent(\App\Project\Domain\Event\ProjectCreatedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'name' => $event->getName()->__toString(),
            'ownerId' => $event->getOwnerId()->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectRenamedEvent(\App\Project\Domain\Event\ProjectRenamedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'oldName' => $event->getOldName()->__toString(),
            'newName' => $event->getNewName()->__toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectDeletedEvent(\App\Project\Domain\Event\ProjectDeletedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectWorkerAddedEvent(\App\Project\Domain\Event\ProjectWorkerAddedEvent $event): string
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

    private function serializeUserCreatedEvent(\App\User\Domain\Event\UserCreatedEvent $event): string
    {
        $data = [
            'userId' => $event->userId->toString(),
            'email' => $event->email->__toString(),
            'createdAt' => $event->createdAt->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeUserDeletedEvent(\App\User\Domain\Event\UserDeletedEvent $event): string
    {
        $data = [
            'userId' => $event->getUserId()->toString(),
            'email' => $event->getEmail()->__toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectWorkerRemovedEvent(\App\Project\Domain\Event\ProjectWorkerRemovedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'userId' => $event->getUserId()->toString(),
            'removedBy' => $event->getRemovedBy()?->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function deserializeEvent(string $eventData, string $eventType): DomainEvent
    {
        try {
            $data = json_decode($eventData, true, 512, JSON_THROW_ON_ERROR);

            // Debug: vyhodíme exception s informáciami
            // if (!isset($data['projectId'])) {
            //     throw new \RuntimeException(
            //         "Missing projectId in event data. Event type: $eventType, Data: " . $eventData
            //     );
            // }

            // This is a simplified deserialization
            // In a real application, you'd want a more robust event deserializer
            return match ($eventType) {
                'App\\Project\\Domain\\Event\\ProjectCreatedEvent' => $this->deserializeProjectCreatedEvent($data),
                'App\\Project\\Domain\\Event\\ProjectRenamedEvent' => $this->deserializeProjectRenamedEvent($data),
                'App\\Project\\Domain\\Event\\ProjectDeletedEvent' => $this->deserializeProjectDeletedEvent($data),
                'App\\Project\\Domain\\Event\\ProjectWorkerAddedEvent' => $this->deserializeProjectWorkerAddedEvent($data),
                'App\\User\\Domain\\Event\\UserCreatedEvent' => $this->deserializeUserCreatedEvent($data),
                'App\\User\\Domain\\Event\\UserDeletedEvent' => $this->deserializeUserDeletedEvent($data),
                'App\\Project\\Domain\\Event\\ProjectWorkerRemovedEvent' => $this->deserializeProjectWorkerRemovedEvent($data),
                default => throw new \RuntimeException("Unknown event type: $eventType")
            };
        } catch (JsonException $e) {
            throw new \RuntimeException('Failed to deserialize event', 0, $e);
        }
    }

    private function deserializeProjectCreatedEvent(array $data): \App\Project\Domain\Event\ProjectCreatedEvent
    {
        return new \App\Project\Domain\Event\ProjectCreatedEvent(
            new Uuid($data['projectId']),
            new \App\Project\Domain\ValueObject\ProjectName($data['name']),
            \App\Shared\ValueObject\Uuid::create($data['ownerId']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectRenamedEvent(array $data): \App\Project\Domain\Event\ProjectRenamedEvent
    {
        return new \App\Project\Domain\Event\ProjectRenamedEvent(
            new Uuid($data['projectId']),
            new \App\Project\Domain\ValueObject\ProjectName($data['oldName']),
            new \App\Project\Domain\ValueObject\ProjectName($data['newName']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectDeletedEvent(array $data): \App\Project\Domain\Event\ProjectDeletedEvent
    {
        return new \App\Project\Domain\Event\ProjectDeletedEvent(
            new Uuid($data['projectId']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectWorkerAddedEvent(array $data): \App\Project\Domain\Event\ProjectWorkerAddedEvent
    {
        return new \App\Project\Domain\Event\ProjectWorkerAddedEvent(
            new Uuid($data['projectId']),
            new Uuid($data['userId']),
            \App\Project\Domain\ValueObject\ProjectRole::create($data['role']),
            isset($data['addedBy']) && $data['addedBy'] ? new Uuid($data['addedBy']) : null,
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeUserCreatedEvent(array $data): \App\User\Domain\Event\UserCreatedEvent
    {
        return new \App\User\Domain\Event\UserCreatedEvent(
            \App\Shared\ValueObject\Uuid::create($data['userId']),
            new \App\Shared\ValueObject\Email($data['email']),
            new \DateTimeImmutable($data['createdAt'])
        );
    }

    private function deserializeUserDeletedEvent(array $data): \App\User\Domain\Event\UserDeletedEvent
    {
        return \App\User\Domain\Event\UserDeletedEvent::create(
            \App\Shared\ValueObject\Uuid::create($data['userId']),
            \App\Shared\ValueObject\Email::fromString($data['email'])
        );
    }

    private function deserializeProjectWorkerRemovedEvent(array $data): \App\Project\Domain\Event\ProjectWorkerRemovedEvent
    {
        return new \App\Project\Domain\Event\ProjectWorkerRemovedEvent(
            new Uuid($data['projectId']),
            new Uuid($data['userId']),
            isset($data['removedBy']) && $data['removedBy'] ? new Uuid($data['removedBy']) : null,
            new \DateTimeImmutable($data['occurredAt'])
        );
    }
}

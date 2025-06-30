<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Shared\Event\DomainEvent;
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
            'App\\Domain\\Project\\Event\\ProjectCreatedEvent' => $this->serializeProjectCreatedEvent($event),
            'App\\Domain\\Project\\Event\\ProjectRenamedEvent' => $this->serializeProjectRenamedEvent($event),
            'App\\Domain\\Project\\Event\\ProjectDeletedEvent' => $this->serializeProjectDeletedEvent($event),
            'App\\Domain\\User\\Event\\UserCreatedEvent' => $this->serializeUserCreatedEvent($event),
            default => throw new \RuntimeException("Unknown event type for serialization: " . get_class($event))
        };
    }

    private function serializeProjectCreatedEvent(\App\Domain\Project\Event\ProjectCreatedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'name' => $event->getName()->__toString(),
            'owner' => [
                'id' => $event->getOwner()->getId()->toString(),
                'email' => $event->getOwner()->getEmail()->getValue()
            ],
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectRenamedEvent(\App\Domain\Project\Event\ProjectRenamedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'oldName' => $event->getOldName()->__toString(),
            'newName' => $event->getNewName()->__toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeProjectDeletedEvent(\App\Domain\Project\Event\ProjectDeletedEvent $event): string
    {
        $data = [
            'projectId' => $event->getProjectId()->toString(),
            'occurredAt' => $event->getOccurredAt()->format(\DateTimeInterface::ATOM)
        ];

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function serializeUserCreatedEvent(\App\Domain\User\Event\UserCreatedEvent $event): string
    {
        $data = [
            'userId' => $event->userId->toString(),
            'email' => $event->email->__toString(),
            'createdAt' => $event->createdAt->format(\DateTimeInterface::ATOM)
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
                'App\\Domain\\Project\\Event\\ProjectCreatedEvent' => $this->deserializeProjectCreatedEvent($data),
                'App\\Domain\\Project\\Event\\ProjectRenamedEvent' => $this->deserializeProjectRenamedEvent($data),
                'App\\Domain\\Project\\Event\\ProjectDeletedEvent' => $this->deserializeProjectDeletedEvent($data),
                'App\\Domain\\User\\Event\\UserCreatedEvent' => $this->deserializeUserCreatedEvent($data),
                default => throw new \RuntimeException("Unknown event type: $eventType")
            };
        } catch (JsonException $e) {
            throw new \RuntimeException('Failed to deserialize event', 0, $e);
        }
    }

    private function deserializeProjectCreatedEvent(array $data): \App\Domain\Project\Event\ProjectCreatedEvent
    {
        // Debug: vypíšeme dáta
        error_log("Deserializing ProjectCreatedEvent with data: " . json_encode($data));
        
        if (!isset($data['name']) || empty($data['name'])) {
            throw new \RuntimeException(
                "Missing or empty name in ProjectCreatedEvent. Data: " . json_encode($data)
            );
        }
        
        return new \App\Domain\Project\Event\ProjectCreatedEvent(
            new Uuid($data['projectId']),
            new \App\Domain\Project\ValueObject\ProjectName($data['name']),
            new \App\Domain\Project\ValueObject\ProjectOwner(
                \App\Domain\Project\ValueObject\UserId::fromString($data['owner']['id']),
                new \App\Shared\ValueObject\Email($data['owner']['email'])
            ),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectRenamedEvent(array $data): \App\Domain\Project\Event\ProjectRenamedEvent
    {
        return new \App\Domain\Project\Event\ProjectRenamedEvent(
            new Uuid($data['projectId']),
            new \App\Domain\Project\ValueObject\ProjectName($data['oldName']),
            new \App\Domain\Project\ValueObject\ProjectName($data['newName']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeProjectDeletedEvent(array $data): \App\Domain\Project\Event\ProjectDeletedEvent
    {
        return new \App\Domain\Project\Event\ProjectDeletedEvent(
            new Uuid($data['projectId']),
            new \DateTimeImmutable($data['occurredAt'])
        );
    }

    private function deserializeUserCreatedEvent(array $data): \App\Domain\User\Event\UserCreatedEvent
    {
        return new \App\Domain\User\Event\UserCreatedEvent(
            \App\Shared\ValueObject\Uuid::create($data['userId']),
            new \App\Shared\ValueObject\Email($data['email']),
            new \DateTimeImmutable($data['createdAt'])
        );
    }
} 
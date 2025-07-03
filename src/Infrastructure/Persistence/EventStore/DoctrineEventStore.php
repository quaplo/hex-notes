<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventSerializer;
use App\Shared\Event\EventStore;
use App\Shared\ValueObject\Uuid;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;

final class DoctrineEventStore implements EventStore
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventSerializer $eventSerializer
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
        return $this->eventSerializer->serialize($event);
    }


    private function deserializeEvent(string $eventData, string $eventType): DomainEvent
    {
        return $this->eventSerializer->deserialize($eventData, $eventType);
    }
}

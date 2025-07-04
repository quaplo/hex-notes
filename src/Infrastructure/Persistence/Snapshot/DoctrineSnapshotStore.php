<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Snapshot;

use App\Shared\Event\SnapshotStore;
use App\Shared\Domain\Model\AggregateSnapshot;
use App\Shared\ValueObject\Uuid;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class DoctrineSnapshotStore implements SnapshotStore
{
    public function __construct(
        private readonly Connection $connection
    ) {}

    /**
     * @throws Exception
     */
    public function save(AggregateSnapshot $snapshot): void
    {
        $sql = '
            INSERT INTO aggregate_snapshots (
                aggregate_id, 
                aggregate_type, 
                version, 
                data, 
                created_at
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                version = VALUES(version),
                data = VALUES(data),
                created_at = VALUES(created_at)
        ';

        $this->connection->executeStatement($sql, [
            $snapshot->getAggregateId()->toString(),
            $this->getAggregateType($snapshot),
            $snapshot->getVersion(),
            json_encode($snapshot->getData(), JSON_THROW_ON_ERROR),
            date('Y-m-d H:i:s')
        ]);
    }

    /**
     * @throws Exception
     */
    public function loadLatest(Uuid $aggregateId, string $aggregateType): ?AggregateSnapshot
    {
        $sql = '
            SELECT aggregate_id, aggregate_type, version, data, created_at
            FROM aggregate_snapshots 
            WHERE aggregate_id = ? AND aggregate_type = ?
            ORDER BY version DESC 
            LIMIT 1
        ';

        $row = $this->connection->fetchAssociative($sql, [
            $aggregateId->toString(),
            $aggregateType
        ]);

        if (!$row) {
            return null;
        }

        return $this->createSnapshotFromRow($row);
    }

    /**
     * @throws Exception
     */
    public function loadByVersion(Uuid $aggregateId, string $aggregateType, int $version): ?AggregateSnapshot
    {
        $sql = '
            SELECT aggregate_id, aggregate_type, version, data, created_at
            FROM aggregate_snapshots 
            WHERE aggregate_id = ? AND aggregate_type = ? AND version = ?
        ';

        $row = $this->connection->fetchAssociative($sql, [
            $aggregateId->toString(),
            $aggregateType,
            $version
        ]);

        if (!$row) {
            return null;
        }

        return $this->createSnapshotFromRow($row);
    }

    /**
     * @throws Exception
     */
    public function deleteOlderThan(Uuid $aggregateId, string $aggregateType, int $version): void
    {
        $sql = '
            DELETE FROM aggregate_snapshots 
            WHERE aggregate_id = ? AND aggregate_type = ? AND version < ?
        ';

        $this->connection->executeStatement($sql, [
            $aggregateId->toString(),
            $aggregateType,
            $version
        ]);
    }

    /**
     * @throws Exception
     */
    public function exists(Uuid $aggregateId, string $aggregateType): bool
    {
        $sql = '
            SELECT COUNT(*) as count
            FROM aggregate_snapshots
            WHERE aggregate_id = ? AND aggregate_type = ?
        ';

        $result = $this->connection->fetchAssociative($sql, [
            $aggregateId->toString(),
            $aggregateType
        ]);

        return (int) $result['count'] > 0;
    }

    /**
     * @throws Exception
     */
    public function removeAll(Uuid $aggregateId, string $aggregateType): void
    {
        $sql = '
            DELETE FROM aggregate_snapshots
            WHERE aggregate_id = ? AND aggregate_type = ?
        ';

        $this->connection->executeStatement($sql, [
            $aggregateId->toString(),
            $aggregateType
        ]);
    }

    /**
     * @throws Exception
     */
    public function getLatestVersion(Uuid $aggregateId, string $aggregateType): ?int
    {
        $sql = '
            SELECT MAX(version) as latest_version
            FROM aggregate_snapshots
            WHERE aggregate_id = ? AND aggregate_type = ?
        ';

        $result = $this->connection->fetchAssociative($sql, [
            $aggregateId->toString(),
            $aggregateType
        ]);

        return $result['latest_version'] !== null ? (int) $result['latest_version'] : null;
    }

    /**
     * Create specific snapshot instance from database row
     * 
     * @param array<string, mixed> $row
     * @throws \JsonException
     */
    private function createSnapshotFromRow(array $row): AggregateSnapshot
    {
        $aggregateId = Uuid::create($row['aggregate_id']);
        $version = (int) $row['version'];
        $data = json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR);

        // Factory pattern based on aggregate type
        return match ($row['aggregate_type']) {
            'Project' => \App\Project\Domain\Model\ProjectSnapshot::create($aggregateId, $version, $data),
            default => throw new \RuntimeException('Unknown aggregate type: ' . $row['aggregate_type'])
        };
    }

    /**
     * Get aggregate type from snapshot class name
     */
    private function getAggregateType(AggregateSnapshot $snapshot): string
    {
        $className = get_class($snapshot);
        
        // Extract type from class name (e.g., ProjectSnapshot -> Project)
        if (preg_match('/([A-Z][a-z]+)Snapshot$/', $className, $matches)) {
            return $matches[1];
        }
        
        throw new \RuntimeException('Cannot determine aggregate type from snapshot class: ' . $className);
    }
}
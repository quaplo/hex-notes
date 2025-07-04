<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use App\Shared\Domain\Model\AggregateSnapshot;
use App\Shared\Event\SnapshotStore;
use App\Shared\ValueObject\Uuid;

final class InMemorySnapshotStore implements SnapshotStore
{
    /** @var array<string, array<int, AggregateSnapshot>> */
    private array $snapshots = [];

    public function save(AggregateSnapshot $snapshot): void
    {
        $key = $this->getKey($snapshot->getAggregateId(), $snapshot->getAggregateType());
        
        if (!isset($this->snapshots[$key])) {
            $this->snapshots[$key] = [];
        }
        
        $this->snapshots[$key][$snapshot->getVersion()] = $snapshot;
    }

    public function loadLatest(Uuid $aggregateId, string $aggregateType): ?AggregateSnapshot
    {
        $key = $this->getKey($aggregateId, $aggregateType);
        
        if (!isset($this->snapshots[$key]) || empty($this->snapshots[$key])) {
            return null;
        }
        
        $maxVersion = max(array_keys($this->snapshots[$key]));
        return $this->snapshots[$key][$maxVersion];
    }

    public function loadByVersion(Uuid $aggregateId, string $aggregateType, int $version): ?AggregateSnapshot
    {
        $key = $this->getKey($aggregateId, $aggregateType);
        
        return $this->snapshots[$key][$version] ?? null;
    }

    public function exists(Uuid $aggregateId, string $aggregateType): bool
    {
        $key = $this->getKey($aggregateId, $aggregateType);
        
        return isset($this->snapshots[$key]) && !empty($this->snapshots[$key]);
    }

    public function removeAll(Uuid $aggregateId, string $aggregateType): void
    {
        $key = $this->getKey($aggregateId, $aggregateType);
        unset($this->snapshots[$key]);
    }

    public function getLatestVersion(Uuid $aggregateId, string $aggregateType): ?int
    {
        $key = $this->getKey($aggregateId, $aggregateType);
        
        if (!isset($this->snapshots[$key]) || empty($this->snapshots[$key])) {
            return null;
        }
        
        return max(array_keys($this->snapshots[$key]));
    }

    private function getKey(Uuid $aggregateId, string $aggregateType): string
    {
        return $aggregateType . ':' . $aggregateId->toString();
    }

    public function clear(): void
    {
        $this->snapshots = [];
    }
}
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

    public function save(AggregateSnapshot $aggregateSnapshot): void
    {
        $key = $this->getKey($aggregateSnapshot->getAggregateId(), $aggregateSnapshot->getAggregateType());

        if (!isset($this->snapshots[$key])) {
            $this->snapshots[$key] = [];
        }

        $this->snapshots[$key][$aggregateSnapshot->getVersion()] = $aggregateSnapshot;
    }

    public function loadLatest(Uuid $uuid, string $aggregateType): ?AggregateSnapshot
    {
        $key = $this->getKey($uuid, $aggregateType);

        if (!isset($this->snapshots[$key]) || empty($this->snapshots[$key])) {
            return null;
        }

        $maxVersion = max(array_keys($this->snapshots[$key]));
        return $this->snapshots[$key][$maxVersion];
    }

    public function loadByVersion(Uuid $uuid, string $aggregateType, int $version): ?AggregateSnapshot
    {
        $key = $this->getKey($uuid, $aggregateType);

        return $this->snapshots[$key][$version] ?? null;
    }

    public function exists(Uuid $uuid, string $aggregateType): bool
    {
        $key = $this->getKey($uuid, $aggregateType);

        return isset($this->snapshots[$key]) && !empty($this->snapshots[$key]);
    }

    public function removeAll(Uuid $uuid, string $aggregateType): void
    {
        $key = $this->getKey($uuid, $aggregateType);
        unset($this->snapshots[$key]);
    }

    public function getLatestVersion(Uuid $uuid, string $aggregateType): ?int
    {
        $key = $this->getKey($uuid, $aggregateType);

        if (!isset($this->snapshots[$key]) || empty($this->snapshots[$key])) {
            return null;
        }

        return max(array_keys($this->snapshots[$key]));
    }

    private function getKey(Uuid $uuid, string $aggregateType): string
    {
        return $aggregateType . ':' . $uuid->toString();
    }

    public function clear(): void
    {
        $this->snapshots = [];
    }
}

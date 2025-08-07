<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Model\AggregateSnapshot;
use App\Shared\ValueObject\Uuid;

interface SnapshotStore
{
    /**
     * Save a snapshot to the store.
     */
    public function save(AggregateSnapshot $aggregateSnapshot): void;

    /**
     * Load the latest snapshot for an aggregate.
     */
    public function loadLatest(Uuid $uuid, string $aggregateType): ?AggregateSnapshot;

    /**
     * Load a specific snapshot by version.
     */
    public function loadByVersion(Uuid $uuid, string $aggregateType, int $version): ?AggregateSnapshot;

    /**
     * Check if a snapshot exists for given aggregate.
     */
    public function exists(Uuid $uuid, string $aggregateType): bool;

    /**
     * Remove all snapshots for an aggregate (for cleanup).
     */
    public function removeAll(Uuid $uuid, string $aggregateType): void;

    /**
     * Get the latest snapshot version for an aggregate.
     */
    public function getLatestVersion(Uuid $uuid, string $aggregateType): ?int;
}

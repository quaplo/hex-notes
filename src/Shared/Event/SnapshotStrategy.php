<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\ValueObject\Uuid;

interface SnapshotStrategy
{
    /**
     * Determine if a snapshot should be created for an aggregate
     */
    public function shouldCreateSnapshot(Uuid $aggregateId, int $currentVersion): bool;
}
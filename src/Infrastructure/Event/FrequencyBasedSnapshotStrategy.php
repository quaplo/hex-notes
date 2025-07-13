<?php

declare(strict_types=1);

namespace App\Infrastructure\Event;

use InvalidArgumentException;
use App\Shared\Event\SnapshotStrategy;
use App\Shared\ValueObject\Uuid;

final readonly class FrequencyBasedSnapshotStrategy implements SnapshotStrategy
{
    public function __construct(
        private int $snapshotFrequency = 10
    ) {
        if ($snapshotFrequency <= 0) {
            throw new InvalidArgumentException('Snapshot frequency must be positive');
        }
    }

    /**
     * Create snapshot every N events
     */
    public function shouldCreateSnapshot(Uuid $uuid, int $currentVersion): bool
    {
        // Create snapshot at version 0 (initial state) and then every N events
        if ($currentVersion === 0) {
            return true;
        }

        return ($currentVersion % $this->snapshotFrequency) === 0;
    }

    public function getSnapshotFrequency(): int
    {
        return $this->snapshotFrequency;
    }
}

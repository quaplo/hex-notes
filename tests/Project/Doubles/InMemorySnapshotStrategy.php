<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use App\Shared\Event\SnapshotStrategy;
use App\Shared\ValueObject\Uuid;

final class InMemorySnapshotStrategy implements SnapshotStrategy
{
    private int $frequency;

    public function __construct(int $frequency = 10)
    {
        $this->frequency = $frequency;
    }

    public function shouldCreateSnapshot(Uuid $aggregateId, int $currentVersion): bool
    {
        return $currentVersion % $this->frequency === 0;
    }

    public function setFrequency(int $frequency): void
    {
        $this->frequency = $frequency;
    }
}
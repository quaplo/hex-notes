<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use App\Shared\Event\SnapshotStrategy;
use App\Shared\ValueObject\Uuid;

final class InMemorySnapshotStrategy implements SnapshotStrategy
{
    public function __construct(private int $frequency = 10)
    {
    }

    public function shouldCreateSnapshot(Uuid $uuid, int $currentVersion): bool
    {
        return $currentVersion % $this->frequency === 0;
    }

    public function setFrequency(int $frequency): void
    {
        $this->frequency = $frequency;
    }
}

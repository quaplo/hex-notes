<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Aggregate\AggregateRoot;
use App\Shared\ValueObject\Uuid;

interface EventStoreRepository
{
    public function save(AggregateRoot $aggregate): void;

    public function load(Uuid $aggregateId): ?AggregateRoot;

    public function exists(Uuid $aggregateId): bool;
}

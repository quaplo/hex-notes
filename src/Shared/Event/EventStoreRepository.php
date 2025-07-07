<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\ValueObject\Uuid;

interface EventStoreRepository
{
    public function save(AggregateRoot $aggregateRoot): void;

    public function load(Uuid $uuid): ?AggregateRoot;

    public function exists(Uuid $uuid): bool;
}

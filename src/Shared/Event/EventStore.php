<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\ValueObject\Uuid;

interface EventStore
{
    /**
     * @param DomainEvent[] $events
     */
    public function append(Uuid $aggregateId, array $events, int $expectedVersion): void;

    /**
     * @return DomainEvent[]
     */
    public function getEvents(Uuid $aggregateId): array;

    /**
     * @return DomainEvent[]
     */
    public function getEventsFromVersion(Uuid $aggregateId, int $fromVersion): array;
}

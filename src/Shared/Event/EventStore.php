<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;

interface EventStore
{
    /**
     * @param DomainEvent[] $events
     */
    public function append(Uuid $uuid, array $events, int $expectedVersion): void;

    /**
     * @return DomainEvent[]
     */
    public function getEvents(Uuid $uuid): array;

    /**
     * @return DomainEvent[]
     */
    public function getEventsFromVersion(Uuid $uuid, int $fromVersion): array;

    /**
     * Find all aggregate IDs by owner ID from ProjectCreatedEvent
     * @return Uuid[]
     */
    public function findProjectAggregatesByOwnerId(Uuid $uuid): array;
}

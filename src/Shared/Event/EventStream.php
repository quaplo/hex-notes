<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;

final class EventStream
{
    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        private readonly Uuid $aggregateId,
        private readonly array $events,
        private readonly int $version
    ) {
    }

    public function getAggregateId(): Uuid
    {
        return $this->aggregateId;
    }

    /**
     * @return DomainEvent[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isEmpty(): bool
    {
        return empty($this->events);
    }

    public function count(): int
    {
        return count($this->events);
    }
}

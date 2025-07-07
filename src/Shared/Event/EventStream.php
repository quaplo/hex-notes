<?php

declare(strict_types=1);

namespace App\Shared\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\ValueObject\Uuid;

final readonly class EventStream
{
    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        private Uuid $uuid,
        private array $events,
        private int $version
    ) {
    }

    public function getAggregateId(): Uuid
    {
        return $this->uuid;
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
        return $this->events === [];
    }

    public function count(): int
    {
        return count($this->events);
    }
}

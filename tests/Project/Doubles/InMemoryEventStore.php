<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use App\Shared\Event\DomainEvent;
use App\Shared\Event\EventStore;
use App\Shared\ValueObject\Uuid;

final class InMemoryEventStore implements EventStore
{
    /** @var array<string, array<DomainEvent>> */
    private array $events = [];
    
    /** @var array<string, int> */
    private array $versions = [];

    public function append(Uuid $aggregateId, array $events, int $expectedVersion): void
    {
        $id = (string)$aggregateId;
        
        if (!isset($this->events[$id])) {
            $this->events[$id] = [];
            $this->versions[$id] = 0;
        }
        
        // Version check for concurrency control
        if ($this->versions[$id] !== $expectedVersion) {
            throw new \RuntimeException("Concurrency conflict: expected version {$expectedVersion}, got {$this->versions[$id]}");
        }
        
        foreach ($events as $event) {
            $this->events[$id][] = $event;
            $this->versions[$id]++;
        }
    }

    public function getEvents(Uuid $aggregateId): array
    {
        $id = (string)$aggregateId;
        return $this->events[$id] ?? [];
    }

    public function getEventsFromVersion(Uuid $aggregateId, int $fromVersion): array
    {
        $id = (string)$aggregateId;
        $allEvents = $this->events[$id] ?? [];
        
        return array_slice($allEvents, $fromVersion);
    }

    public function getVersion(Uuid $aggregateId): int
    {
        $id = (string)$aggregateId;
        return $this->versions[$id] ?? 0;
    }

    // Testing helper methods
    
    public function clear(): void
    {
        $this->events = [];
        $this->versions = [];
    }

    public function getEventCount(Uuid $aggregateId): int
    {
        $id = (string)$aggregateId;
        return count($this->events[$id] ?? []);
    }

    public function getAllEvents(): array
    {
        $allEvents = [];
        foreach ($this->events as $aggregateEvents) {
            $allEvents = array_merge($allEvents, $aggregateEvents);
        }
        return $allEvents;
    }
}
<?php

declare(strict_types=1);

namespace App\Tests\Project\Doubles;

use RuntimeException;
use App\Project\Domain\Event\ProjectCreatedEvent;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Event\EventStore;
use App\Shared\ValueObject\Uuid;

final class InMemoryEventStore implements EventStore
{
    /** @var array<string, array<DomainEvent>> */
    private array $events = [];

    /** @var array<string, int> */
    private array $versions = [];

    public function append(Uuid $uuid, array $events, int $expectedVersion): void
    {
        $id = (string)$uuid;

        if (!isset($this->events[$id])) {
            $this->events[$id] = [];
            $this->versions[$id] = 0;
        }

        // Version check for concurrency control
        if ($this->versions[$id] !== $expectedVersion) {
            throw new RuntimeException("Concurrency conflict: expected version {$expectedVersion}, got {$this->versions[$id]}");
        }

        foreach ($events as $event) {
            $this->events[$id][] = $event;
            $this->versions[$id]++;
        }
    }

    public function getEvents(Uuid $uuid): array
    {
        $id = (string)$uuid;
        return $this->events[$id] ?? [];
    }

    public function getEventsFromVersion(Uuid $uuid, int $fromVersion): array
    {
        $id = (string)$uuid;
        $allEvents = $this->events[$id] ?? [];

        return array_slice($allEvents, $fromVersion);
    }

    public function findProjectAggregatesByOwnerId(Uuid $uuid): array
    {
        $aggregateIds = [];

        foreach ($this->events as $aggregateId => $events) {
            foreach ($events as $event) {
                if (!$event instanceof ProjectCreatedEvent) {
                    continue;
                }
                if (!$event->getOwnerId()->equals($uuid)) {
                    continue;
                }
                $aggregateIds[] = Uuid::create($aggregateId);
                break;
                // Only need to find one ProjectCreatedEvent per aggregate
            }
        }

        return $aggregateIds;
    }

    public function getVersion(Uuid $uuid): int
    {
        $id = (string)$uuid;
        return $this->versions[$id] ?? 0;
    }

    // Testing helper methods

    public function clear(): void
    {
        $this->events = [];
        $this->versions = [];
    }

    public function getEventCount(Uuid $uuid): int
    {
        $id = (string)$uuid;
        return count($this->events[$id] ?? []);
    }

    public function getAllEvents(): array
    {
        $allEvents = [];
        foreach ($this->events as $event) {
            $allEvents = array_merge($allEvents, $event);
        }
        return $allEvents;
    }
}

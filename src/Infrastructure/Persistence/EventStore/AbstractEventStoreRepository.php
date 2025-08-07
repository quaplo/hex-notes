<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Event\EventDispatcher;
use App\Shared\Event\EventStore;
use App\Shared\Event\EventStoreRepository;
use App\Shared\ValueObject\Uuid;

abstract class AbstractEventStoreRepository implements EventStoreRepository
{
    public function __construct(
        protected readonly EventStore $eventStore,
        protected readonly EventDispatcher $eventDispatcher,
    ) {
    }

    public function save(AggregateRoot $aggregateRoot): void
    {
        $events = $aggregateRoot->getDomainEvents();

        if ($events === []) {
            return;
        }

        // Oprava: expectedVersion je aktuálna verzia aggregate
        $expectedVersion = $aggregateRoot->getVersion();

        $this->eventStore->append(
            $aggregateRoot->getId(),
            $events,
            $expectedVersion
        );

        // Dispatch events after successful save
        $this->eventDispatcher->dispatch($events);

        $aggregateRoot->clearDomainEvents();
    }

    public function load(Uuid $uuid): ?AggregateRoot
    {
        $events = $this->eventStore->getEvents($uuid);

        if ($events === []) {
            return null;
        }

        $aggregateRoot = $this->createAggregate();

        foreach ($events as $event) {
            $aggregateRoot->replayEvent($event);
        }

        return $aggregateRoot;
    }

    public function exists(Uuid $uuid): bool
    {
        $events = $this->eventStore->getEvents($uuid);

        return $events !== [];
    }

    abstract protected function createAggregate(): AggregateRoot;
}

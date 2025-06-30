<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Event\EventDispatcher;
use App\Shared\Event\EventStore;
use App\Shared\Event\EventStoreRepository;
use App\Shared\ValueObject\Uuid;

abstract class AbstractEventStoreRepository implements EventStoreRepository
{
    public function __construct(
        protected readonly EventStore $eventStore,
        protected readonly EventDispatcher $eventDispatcher
    ) {
    }

    public function save(AggregateRoot $aggregate): void
    {
        $events = $aggregate->getDomainEvents();
        
        if (empty($events)) {
            return;
        }

        // Pre nový aggregate (verzia 0) očakávame verziu 0
        // Pre existujúci aggregate očakávame aktuálnu verziu
        $expectedVersion = $aggregate->getVersion() === 0 ? 0 : $aggregate->getVersion() - count($events);

        $this->eventStore->append(
            $aggregate->getId(),
            $events,
            $expectedVersion
        );

        // Dispatch events after successful save
        $this->eventDispatcher->dispatch($events);

        $aggregate->clearDomainEvents();
    }

    public function load(Uuid $aggregateId): ?AggregateRoot
    {
        $events = $this->eventStore->getEvents($aggregateId);
        
        if (empty($events)) {
            return null;
        }

        $aggregate = $this->createAggregate();
        
        foreach ($events as $event) {
            $aggregate->replayEvent($event);
        }

        return $aggregate;
    }

    public function exists(Uuid $aggregateId): bool
    {
        $events = $this->eventStore->getEvents($aggregateId);
        return !empty($events);
    }

    abstract protected function createAggregate(): AggregateRoot;
} 
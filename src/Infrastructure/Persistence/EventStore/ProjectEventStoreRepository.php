<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Event\EventDispatcher;
use App\Shared\Event\EventStore;
use App\Shared\ValueObject\Uuid;

final class ProjectEventStoreRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly EventDispatcher $eventDispatcher
    ) {
    }

    public function save(Project $project): void
    {
        $events = $project->getDomainEvents();

        if (empty($events)) {
            return;
        }

        // Oprava: expectedVersion je aktuálna verzia aggregate
        $expectedVersion = $project->getVersion();

        $this->eventStore->append(
            $project->getId(),
            $events,
            $expectedVersion
        );

        // Dispatch events after successful save
        $this->eventDispatcher->dispatch($events);

        $project->clearDomainEvents();
    }

    public function load(Uuid $aggregateId): ?Project
    {
        $events = $this->eventStore->getEvents($aggregateId);

        if (empty($events)) {
            return null;
        }

        $project = $this->createAggregate();

        foreach ($events as $event) {
            $project->replayEvent($event);
        }

        return $project;
    }

    public function exists(Uuid $aggregateId): bool
    {
        $events = $this->eventStore->getEvents($aggregateId);
        return !empty($events);
    }

    private function createAggregate(): Project
    {
        return Project::createEmpty();
    }
}

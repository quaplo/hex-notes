<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\EventStore;

use App\Project\Domain\Model\Project;
use App\Project\Domain\Model\ProjectSnapshotFactory;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Event\EventDispatcher;
use App\Shared\Event\EventStore;
use App\Shared\Event\SnapshotStore;
use App\Shared\Event\SnapshotStrategy;
use App\Shared\ValueObject\Uuid;

final class ProjectEventStoreRepository implements ProjectRepositoryInterface
{
    private const AGGREGATE_TYPE = 'Project';

    public function __construct(
        private readonly EventStore $eventStore,
        private readonly EventDispatcher $eventDispatcher,
        private readonly SnapshotStore $snapshotStore,
        private readonly ProjectSnapshotFactory $snapshotFactory,
        private readonly SnapshotStrategy $snapshotStrategy
    ) {
    }

    public function save(Project $project): void
    {
        $events = $project->getDomainEvents();

        if (empty($events)) {
            return;
        }

        $expectedVersion = $project->getVersion();

        // Save events to event store
        $this->eventStore->append(
            $project->getId(),
            $events,
            $expectedVersion
        );

        // Calculate new version after events
        $newVersion = $expectedVersion + count($events);

        // Check if we should create a snapshot
        if ($this->snapshotStrategy->shouldCreateSnapshot($project->getId(), $newVersion)) {
            try {
                $snapshot = $this->snapshotFactory->createSnapshot($project, $newVersion);
                $this->snapshotStore->save($snapshot);
            } catch (\Exception $e) {
                // Log error but don't fail the save operation
                // Snapshots are for performance optimization, not critical for correctness
                error_log("Failed to create snapshot for project {$project->getId()}: " . $e->getMessage());
            }
        }

        // Dispatch events and clear domain events
        $this->eventDispatcher->dispatch($events);
        $project->clearDomainEvents();
    }

    public function load(Uuid $aggregateId): ?Project
    {
        // Try to load from snapshot first
        $snapshot = $this->snapshotStore->loadLatest($aggregateId, self::AGGREGATE_TYPE);
        
        if ($snapshot !== null) {
            // Restore project from snapshot
            $project = $this->snapshotFactory->restoreFromSnapshot($snapshot);
            
            // Load events after the snapshot version
            $eventsAfterSnapshot = $this->eventStore->getEventsFromVersion(
                $aggregateId,
                $snapshot->getVersion() + 1
            );
            
            // Replay events after snapshot
            foreach ($eventsAfterSnapshot as $event) {
                $project->replayEvent($event);
            }
            
            return $project;
        }

        // No snapshot found, load all events (traditional event sourcing)
        $events = $this->eventStore->getEvents($aggregateId);

        if (empty($events)) {
            return null;
        }

        $project = Project::createEmpty();

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
}
